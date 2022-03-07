<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Enums\Status;
use App\Events\TransactionSuccessEvent;
use App\Models\Transaction;
use App\Repositories\EarningRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\Providers;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Http\Client\RequestException;
use Propaganistas\LaravelPhone\PhoneNumber;

class TandaEventRepository extends EventRepository
{
    public static function requestSuccess(TandaRequest $tandaRequest)
    {
        // Update Transaction
        $transaction = Transaction::find($tandaRequest->relation_id);
        Transaction::updateStatus($transaction, Status::COMPLETED);

        $method = $transaction->payment->subtype;

        if($method == 'VOUCHER') {
            $bal = $transaction->account->voucher->balance;
            $vtext = " New Voucher balance is KES$bal.";
        } else {
            $method = 'MPESA';
            $vtext = '';
        }

        $code = config('services.at.ussd.code');

        $destination = $tandaRequest->destination;
        $sender = $transaction->account->phone;

        $amount = $transaction->amount;
        $date = $tandaRequest->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $provider = $tandaRequest->provider;

        switch($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
//                Get Points Earned
                if($provider == Providers::FAIBA) $totalEarnings = .07 * $transaction->amount; else
                    $totalEarnings = .06 * $transaction->amount;

                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

                $phone = ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+');

//                Send SMS
                if($phone != $sender) {
                    $message = "You have purchased {$amount} airtime for {$phone} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                    SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE);

                    $message = "Congratulations! You have received {$amount} airtime from Sidooh account {$sender} on {$date}. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";
                } else {
                    $message = "You have purchased {$amount} airtime from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                }
                SidoohNotify::notify([$phone], $message, EventType::AIRTIME_PURCHASE);

                break;

            case Providers::KPLC_POSTPAID:
                //                Get Points Earned
                $totalEarnings = .017 * $transaction->amount;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                SidoohNotify::notify([$sender], $message, EventType::UTILITY_PAYMENT);

                break;


            case Providers::KPLC_PREPAID:
//                Get Points Earned
                $totalEarnings = .017 * $transaction->amount;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $details = $tandaRequest->result;
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                $message .= "\nTokens: {$details[0]['value']}\nUnits: {$details[1]['value']}";

                SidoohNotify::notify([$sender], $message, EventType::UTILITY_PAYMENT);

                break;

            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:
//                Get Points Earned
                $totalEarnings = .003 * $transaction->amount;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                SidoohNotify::notify([$sender], $message, EventType::UTILITY_PAYMENT);

                break;

            case Providers::NAIROBI_WTR:
                //                Get Points Earned
                $totalEarnings = 5;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                SidoohNotify::notify([$sender], $message, EventType::UTILITY_PAYMENT);

                break;

        }
    }

    /**
     * @throws RequestException
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

        $provider = $tandaRequest->provider;

        $voucher = SidoohPayments::creditVoucher($transaction->account_id, $amount);

        $transaction->status = Status::REIMBURSED;
        $transaction->save();

        $message = match ($provider) {
            Providers::FAIBA, Providers::SAFARICOM, Providers::AIRTEL, Providers::TELKOM => "Sorry! We could not complete your KES{$amount} airtime purchase for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher['balance']}.",
            Providers::KPLC_POSTPAID, Providers::NAIROBI_WTR, Providers::KPLC_PREPAID, Providers::DSTV, Providers::GOTV, Providers::ZUKU, Providers::STARTIMES => "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}."
        };

        SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE_FAILURE);
    }
}
