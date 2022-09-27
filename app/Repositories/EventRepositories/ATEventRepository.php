<?php

namespace App\Repositories\EventRepositories;

use App\Enums\Description;
use App\Enums\EventType;
use App\Enums\Status;
use App\Events\TransactionSuccessEvent;
use App\Models\ATAirtimeResponse;
use App\Repositories\EarningRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use Illuminate\Support\Facades\Log;

class ATEventRepository
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function airtimePurchaseFailed(ATAirtimeResponse $airtimeResponse): void
    {
        SidoohNotify::notify(admin_contacts(), "ERROR:AIRTIME\n$airtimeResponse->phone", EventType::ERROR_ALERT);
        Log::info('Airtime Failure SMS Sent');

        $phone = ltrim($airtimeResponse->phone, '+');

        $amount = explode('.', explode(' ', $airtimeResponse->amount)[1])[0];
        $date = $airtimeResponse->airtimeRequest->created_at->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        $transaction = $airtimeResponse->airtimeRequest->transaction;
        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $voucher = SidoohPayments::creditVoucher($transaction->account_id, $amount, Description::VOUCHER_REFUND);

        $message = "Sorry! We could not complete your KES{$amount} airtime purchase for {$phone} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher['balance']}.";

        SidoohNotify::notify([$phone], $message, EventType::AIRTIME_PURCHASE_FAILURE);
    }

    /**
     * @throws \Exception
     */
    public static function airtimePurchaseSuccess(ATAirtimeResponse $airtimeResponse): void
    {
        $transaction = $airtimeResponse->airtimeRequest->transaction;

        $phone = ltrim($airtimeResponse->phone, '+');
        $sender = SidoohAccounts::find($transaction->account_id)['phone'];
        $method = $transaction->payment->subtype;
        $provider = getTelcoFromPhone($transaction->destination);

        $rateConfig = config("services.tanda.discounts.$provider", ['type' => '$', 'value' => 0]);
        $totalEarnings = match ($rateConfig['type']) {
            '%' => $rateConfig['value'] * $transaction->amount,
            '$' => $rateConfig['value']
        };
        if ($totalEarnings <= 0) {
            Log::error('...[REP - AT]: New Calculation... Failed!!!', [$rateConfig, $totalEarnings]);

            return;
        }

        $pointsEarned = EarningRepository::getPointsEarned($transaction, $totalEarnings);

        $code = config('services.at.ussd.code');

        if ($method == 'VOUCHER') {
            $voucher = $transaction->payment->extra;
            $bal = 'Ksh' . number_format($voucher['balance'], 2);
            $vtext = " New Voucher balance is $bal.";
        } else {
            $method = 'MPESA';
            $vtext = '';
        }

        self::statusUpdate($airtimeResponse);

        $amount = str_replace(' ', '', explode('.', $airtimeResponse->amount)[0]);
        $date = $airtimeResponse->updated_at->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        if ($phone != $sender) {
            $message = "You have purchased {$amount} airtime for {$phone} from your Sidooh account on {$date} using $method. You have received {$pointsEarned} cashback.$vtext";

            SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE);

            $message = "Congratulations! You have received {$amount} airtime from Sidooh account {$sender} on {$date}. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";
        } else {
            $message = "You have purchased {$amount} airtime from your Sidooh account on {$date} using $method. You have received {$pointsEarned} cashback.$vtext";
        }

        SidoohNotify::notify([$phone], $message, EventType::AIRTIME_PURCHASE);
    }

    public static function statusUpdate(ATAirtimeResponse $airtimeResponse): void
    {
        Log::info('...[REP - AT] Status Update...');

        $airtimeRequest = $airtimeResponse->airtimeRequest;

        $responses = $airtimeRequest->airtimeResponses;

//        TODO:: Remove Sent from successful
//        || $value->status == 'Sent'
        $successful = $responses->filter(fn($value) => $value->status == 'Success' || $value->status == 'Sent');

        if (count($successful) == count($responses)) {
            $totalEarned = explode(' ', $airtimeRequest->discount)[1];

            TransactionSuccessEvent::dispatch($airtimeRequest->transaction, $totalEarned);
        }
    }
}
