<?php

namespace App\Listeners;

use App\Events\TransactionSuccessEvent;
use App\Helpers\SidoohNotify\EventTypes;
use App\Models\Transaction;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Events\KyandaTransactionSuccessEvent;
use Nabcellent\Kyanda\Library\Providers;
use Propaganistas\LaravelPhone\PhoneNumber;

class KyandaTransactionSuccess
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param KyandaTransactionSuccessEvent $event
     * @return void
     */
    public function handle(KyandaTransactionSuccessEvent $event)
    {
        //
        Log::info('----------------- Kyanda Transaction Success ');
        Log::info($event->transaction->request->provider);

//                Update Transaction
        $transaction = Transaction::find($event->transaction->request->relation_id);
        (new TransactionRepository())->updateStatus($transaction, 'completed');

        $method = $transaction->payment->subtype;

        if ($method == 'VOUCHER') {
            $bal = $transaction->account->voucher->balance;
            $vtext = " New Voucher balance is KES$bal.";
        } else {
            $method = 'MPESA';
            $vtext = '';
        }

        $code = config('services.at.ussd.code');

        $destination = $event->transaction->destination;
        $sender = $transaction->account->phone;

        $amount = $transaction->amount;
        $date = $event->transaction->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $provider = $event->transaction->request->provider;

        switch ($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
            case Providers::EQUITEL:

//                Get Points Earned
                if ($provider == Providers::FAIBA)
                    $totalEarnings = .09 * $transaction->amount;
                elseif ($provider == Providers::EQUITEL)
                    $totalEarnings = .05 * $transaction->amount;
                else
                    $totalEarnings = .06 * $transaction->amount;

                $userEarnings = $this->getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

                $phone = ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+');

//                Send SMS
                if ($phone != $sender) {
                    $message = "You have purchased {$amount} airtime for {$phone} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                    NotificationRepository::sendSMS([$sender], $message, EventTypes::AIRTIME_PURCHASE);


                    $message = "Congratulations! You have received {$amount} airtime from Sidooh account {$sender} on {$date}. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";

                    NotificationRepository::sendSMS([$phone], $message, EventTypes::AIRTIME_PURCHASE);
                } else {

                    $message = "You have purchased {$amount} airtime from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                    NotificationRepository::sendSMS([$phone], $message, EventTypes::AIRTIME_PURCHASE);
                }

                break;

            case Providers::KPLC_POSTPAID:
                //                Get Points Earned
                $totalEarnings = .01 * $transaction->amount;
                $userEarnings = $this->getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                NotificationRepository::sendSMS([$sender], $message, EventTypes::UTILITY_PAYMENT);

                break;


            case Providers::KPLC_PREPAID:
//                Get Points Earned
                $totalEarnings = .015 * $transaction->amount;
                $userEarnings = $this->getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $details = (object)$event->transaction->details;
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                $message .= "\nTokens: {$details->tokens}\nUnits: {$details->units}";

                NotificationRepository::sendSMS([$sender], $message, EventTypes::UTILITY_PAYMENT);

                break;

            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:
//                Get Points Earned
                $totalEarnings = .0025 * $transaction->amount;
                $userEarnings = $this->getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

            NotificationRepository::sendSMS([$sender], $message, EventTypes::UTILITY_PAYMENT);

                break;

            case Providers::NAIROBI_WTR:
                //                Get Points Earned
                $totalEarnings = 5;
                $userEarnings = $this->getPointsEarned($totalEarnings);

//                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                NotificationRepository::sendSMS([$sender], $message, EventTypes::UTILITY_PAYMENT);

                break;

            case Providers::FAIBA_B:
//                Get Points Earned
                $totalEarnings = .09 * $transaction->amount;
                $userEarnings = $this->getPointsEarned($totalEarnings);

                //                Update Earnings
                event(new TransactionSuccessEvent($transaction, $totalEarnings));

//                Send SMS
                $message = "You have purchased {$amount} bundles from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                NotificationRepository::sendSMS([$sender], $message, EventTypes::AIRTIME_PURCHASE);

                break;

        }

    }

//    TODO: Refactor function to helper file?
    public function getPointsEarned(float $discount): string
    {
        $e = $discount * config('services.sidooh.earnings.users_percentage');
        return 'KES' . $e / 6;
    }
}
