<?php

namespace App\Repositories\EventRepositories;

use App\Enums\Description;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Events\TransactionSuccessEvent;
use App\Models\Transaction;
use App\Repositories\EarningRepository;
use App\Repositories\ProductRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\Providers;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;

class TandaEventRepository extends EventRepository
{
    public static function getProvider(TandaRequest $tandaRequest, Transaction $transaction)
    {
        $provider = $tandaRequest->provider;

        if (empty($provider)) {
            $productId = $transaction->product_id;
            $descriptionArray = explode(" ", $transaction->description);

            $provider = $productId == ProductType::AIRTIME->value ? getTelcoFromPhone($transaction->destination)
                : $descriptionArray[0];

            $tandaRequest->provider = $provider;

            if (empty($tandaRequest->destination)) {
                $tandaRequest->destination = $descriptionArray[1];
            }

            $tandaRequest->save();
        }

        return $provider;
    }

    public static function requestSuccess(TandaRequest $tandaRequest)
    {
        // Update Transaction
        if ($tandaRequest->relation_id) {
            $transaction = Transaction::find($tandaRequest->relation_id);
        } else {
            $transaction = Transaction::whereStatus(Status::PENDING->name)->whereType(TransactionType::PAYMENT->name)
                ->whereAmount($tandaRequest->amount)->where('destination', 'LIKE', "%" . $tandaRequest->destination)
                ->whereDate('createdAt', '<', $tandaRequest->created_at);
            $tandaRequest->relation_id = $transaction->id;
            $tandaRequest->save();
        }

        if ($transaction->status == Status::COMPLETED) {
            SidoohNotify::notify([
                '254714611696',
                '254110039317'
            ], "ERROR:TANDA REQUEST\nTransaction $transaction seems to have been completed already. Confirm!!!", EventType::ERROR_ALERT);
            return;
        }

        $provider = self::getProvider($tandaRequest, $transaction);
        // Handle for KPLC provider :: https://products-dashboard-zwwy5he2ia-uc.a.run.app/transactions/13159

        $account = SidoohAccounts::find($transaction->account_id);

        if ($transaction->payment->subtype === PaymentMethod::VOUCHER->name) {
            $method = PaymentMethod::VOUCHER->name;

            $voucher = $transaction->payment->extra;
            $bal = 'Ksh' . number_format($voucher["balance"], 2);
            $vtext = " New Voucher balance is $bal.";
        } else {
            $method = $transaction->payment->type;
            $vtext = '';

            $extra = $transaction->payment->extra;
            if (isset($extra['debit_account']) && $account['phone'] !== $extra['debit_account']) {
                $method = "OTHER " . $method;
            }
        }

        $code = config('services.at.ussd.code');

        $destination = $transaction->destination;
        $sender = $account["phone"];

        $amount = 'Ksh' . number_format($transaction->amount, 2);
        $date = $tandaRequest->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));
        $eventType = EventType::UTILITY_PAYMENT;

        $rateConfig = config("services.tanda.discounts.$provider", ['type' => '$', 'value' => 0]);
        $totalEarnings = match ($rateConfig['type']) {
            '%' => $rateConfig['value'] * $transaction->amount,
            '$' => $rateConfig['value']
        };
        if ($totalEarnings <= 0) {
            Log::error('...[REP - TANDA]: New Calculation... Failed!!!', [$rateConfig, $totalEarnings]);
            return;
        }


        switch ($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
                //  Get Points Earned
                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);
                $phone = ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+');
                $eventType = EventType::AIRTIME_PURCHASE;

                //  Send SMS
                if ($phone != $sender) {
                    $message = "You have purchased $amount airtime for $phone from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";

                    SidoohNotify::notify([$sender], $message, $eventType);

                    $message = "Congratulations! You have received $amount airtime from Sidooh account $sender on $date. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";
                } else {
                    $message = "You have purchased $amount airtime from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";
                }

                $sender = $phone;
                break;
//            case Providers::KPLC_POSTPAID:
//                //  Get Points Earned
//                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);
//
//                //  Send SMS
//                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";
//                break;
            case Providers::KPLC_PREPAID:
                //  Get Points Earned
                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);

                ['Token' => $tokens, 'Units' => $units] = array_column($tandaRequest->result, 'value', 'label');

                //  Send SMS
                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";
                $message .= "\nTokens: $tokens\nUnits: $units";
                break;
            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:
            case Providers::KPLC_POSTPAID:
            case Providers::NAIROBI_WTR:
                //  Get Points Earned
                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);

                //  Send SMS
                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";
                break;
//            case Providers::NAIROBI_WTR:
//                //  Get Points Earned
//                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);
//
//                //  Send SMS
//                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";
//                break;
        }

        //  Update Transaction & Earnings
        TransactionSuccessEvent::dispatch($transaction, $totalEarnings);
        ProductRepository::syncAccounts($account, $provider, $destination);
        SidoohNotify::notify([$sender], $message, $eventType);

        Log::info('...[REP - TANDA]: Completed Transaction...');
    }

    /**
     * @throws RequestException|AuthenticationException
     */
    public static function requestFailed(TandaRequest $tandaRequest)
    {
        // Update Transaction
        $transaction = Transaction::find($tandaRequest->relation_id);
        Transaction::updateStatus($transaction, Status::FAILED);

        $destination = $tandaRequest->destination;
        $sender = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $date = $tandaRequest->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $provider = self::getProvider($tandaRequest, $transaction);

        $response = SidoohPayments::creditVoucher($transaction->account_id, $amount, Description::VOUCHER_REFUND);
        [$voucher,] = $response['data'];

        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $amount = "Ksh" . number_format($amount, 2);

        $message = match ($transaction->product_id) {
            ProductType::AIRTIME->value => "Sorry! We could not complete your $amount airtime purchase for $destination on $date. We have added $amount to your voucher account. New Voucher balance is {$voucher['balance']}.",
            ProductType::UTILITY->value => "Sorry! We could not complete your payment to $provider of $amount for $destination on $date. We have added $amount to your voucher account. New Voucher balance is {$voucher["balance"]}."
        };

        SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE_FAILURE);
    }
}
