<?php

namespace App\Listeners;

use App\Enums\EventType;
use App\Events\VoucherPurchaseEvent;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use Illuminate\Support\Facades\Log;

class VoucherPurchaseSuccess
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
     * @param VoucherPurchaseEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(VoucherPurchaseEvent $event)
    {
        Log::info('----------------- Voucher Purchase Success ');

        $amount = $event->transaction->amount;
        $account = SidoohAccounts::find($event->transaction->account_id);

        $phone = ltrim($account['phone'], '+');

        $date = $event->voucher->updated_at->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        $message = "Congratulations! You have successfully purchased a voucher ";
        $message .= "worth Ksh{$amount} on {$date}.\n\n";
        $message .= config('services.sidooh.tagline');

        SidoohNotify::sendSMSNotification([$phone], $message, EventType::VOUCHER_PURCHASE);

    }
}
