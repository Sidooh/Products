<?php

namespace App\Listeners;

use App\Enums\EventType;
use App\Enums\Status;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use DrH\Tanda\Events\TandaRequestFailedEvent;
use DrH\Tanda\Library\Providers;
use Exception;
use Illuminate\Support\Facades\Log;

class TandaRequestFailed
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
     * @param TandaRequestFailedEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(TandaRequestFailedEvent $event)
    {
        Log::info('----------------- Tanda Request Failed ', [
            'id'      => $event->request->id,
            'message' => $event->request->message
        ]);

        // Update Transaction
        $transaction = Transaction::find($event->request->relation_id);
        Transaction::updateStatus($transaction, Status::FAILED);

        $destination = $event->request->destination;
        $sender = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $date = $event->request->updated_at->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        $provider = $event->request->provider;

        $voucher = Voucher::whereAccountId($transaction->account_id)->firstOrFail();
        $voucher->balance += $amount;
        $voucher->save();

        $transaction->status = Status::REIMBURSED;
        $transaction->save();

        $message = match ($provider) {
            Providers::FAIBA, Providers::SAFARICOM, Providers::AIRTEL, Providers::TELKOM => "Sorry! We could not complete your KES{$amount} airtime purchase for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.",
            Providers::KPLC_POSTPAID, Providers::NAIROBI_WTR, Providers::KPLC_PREPAID, Providers::DSTV, Providers::GOTV, Providers::ZUKU, Providers::STARTIMES => "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}."
        };

        SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE_FAILURE);
    }
}
