<?php

namespace App\Listeners;

use App\Helpers\SidoohNotify\EventTypes;
use App\Models\Transaction;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Events\KyandaTransactionFailedEvent;
use Nabcellent\Kyanda\Library\Providers;

class KyandaTransactionFailed
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
     * @param KyandaTransactionFailedEvent $event
     * @return void
     */
    public function handle(KyandaTransactionFailedEvent $event)
    {
        //
        Log::info('----------------- Kyanda Transaction Failed ');
        Log::error($event->transaction);


        //                Update Transaction
        $transaction = Transaction::find($event->transaction->request->relation_id);
        (new TransactionRepository())->updateStatus($transaction, 'Failed');

        $destination = $event->transaction->destination;
        $sender = $transaction->account->phone;

        $amount = $transaction->amount;
        $date = $event->transaction->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $provider = $event->transaction->request->provider;

        $voucher = $transaction->account->voucher;
        $voucher->in += $amount;
        $voucher->save();

        $transaction->status = 'reimbursed';
        $transaction->save();

        switch ($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
            case Providers::EQUITEL:

            $message = "Sorry! We could not complete your KES{$amount} airtime purchase for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
            NotificationRepository::sendSMS([$sender], $message, EventTypes::AIRTIME_PURCHASE_FAILURE);

                break;

            case Providers::KPLC_POSTPAID:


//                $message = "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
//                (new AfricasTalkingApi())->sms($sender, $message);
//
//                break;

            case Providers::KPLC_PREPAID:

//                $message = "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
//                (new AfricasTalkingApi())->sms($sender, $message);
//
//                break;

            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:

//                $message = "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
//                (new AfricasTalkingApi())->sms($sender, $message);
//
//                break;

            case Providers::NAIROBI_WTR:

//                $message = "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
//                (new AfricasTalkingApi())->sms($sender, $message);
//
//                break;

            case Providers::FAIBA_B:


            $message = "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";

            NotificationRepository::sendSMS([$sender], $message, EventTypes::UTILITY_PAYMENT_FAILURE);

            break;

        }

    }
}
