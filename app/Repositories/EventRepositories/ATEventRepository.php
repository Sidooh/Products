<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Models\AirtimeResponse;
use App\Repositories\EarningRepository;
use App\Services\SidoohNotify;
use Exception;
use Illuminate\Support\Facades\Log;

class ATEventRepository
{
    public static function airtimePurchaseFailed(AirtimeResponse $airtimeResponse)
    {
        try {
            SidoohNotify::notify([
                '254714611696',
                '254711414987',
                '254721309253'
            ], "ERROR:AIRTIME\n{$airtimeResponse->phoneNumber}", EventType::ERROR_ALERT);
            Log::info("Airtime Failure SMS Sent");
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }

//        TODO: Refund money to voucher
        $phone = ltrim($airtimeResponse->phoneNumber, '+');
        $account = $airtimeResponse->request->transaction->account;

        $amount = explode(".", explode(" ", $airtimeResponse->amount)[1])[0];
        $date = $airtimeResponse->request->created_at->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

//        TODO: Find a better way to get the transaction cause of gateway error from AT and transaction seems empty
//        $transaction = new $airtimeResponse->request->transaction;
//        $transaction->status = Status::REFUNDED;
//        $transaction->save();

        $voucher = $account->voucher;
        $voucher->balance += (double)$amount;
        $voucher->save();

        $message = "Sorry! We could not complete your KES{$amount} airtime purchase for {$phone} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";

        SidoohNotify::notify([$phone], $message, EventType::AIRTIME_PURCHASE_FAILURE);
    }

    public static function airtimePurchaseSuccess(AirtimeResponse $airtimeResponse)
    {
        $phone = ltrim($airtimeResponse->phoneNumber, '+');
        $sender = $airtimeResponse->request->transaction->account->phone;
        $method = $airtimeResponse->request->transaction->payment->subtype;

        $amount = str_replace(' ', '', explode(".", $airtimeResponse->amount)[0]);
        $date = $airtimeResponse->updated_at->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        $points_earned = EarningRepository::getPointsEarned(explode(' ', $airtimeResponse->discount)[1]);

        $code = config('services.at.ussd.code');

        if($method == 'VOUCHER') {
            $bal = $airtimeResponse->request->transaction->account->voucher->balance;
            $vtext = " New Voucher balance is KES$bal.";
        } else {
            $method = 'MPESA';
            $vtext = '';
        }

        (new TransactionRepository())->statusUpdate($airtimeResponse);

        if($phone != $sender) {
            $message = "You have purchased {$amount} airtime for {$phone} from your Sidooh account on {$date} using $method. You have received {$points_earned} cashback.$vtext";

            SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE);

            $message = "Congratulations! You have received {$amount} airtime from Sidooh account {$sender} on {$date}. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";
        } else {
            $message = "You have purchased {$amount} airtime from your Sidooh account on {$date} using $method. You have received {$points_earned} cashback.$vtext";
        }

        SidoohNotify::notify([$phone], $message, EventType::AIRTIME_PURCHASE);
    }
}
