<?php

namespace App\Listeners;

use App\Enums\EventType;
use App\Events\SubscriptionPurchaseEvent;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use Illuminate\Support\Facades\Log;
use NumberFormatter;

class SubscriptionPurchaseSuccess
{

    public bool $afterCommit = true;

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
     * @param SubscriptionPurchaseEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(SubscriptionPurchaseEvent $event)
    {
        Log::info('----------------- Subscription Purchase Success');

        $type = $event->subscription->subscriptionType;
        $account = SidoohAccounts::find($event->transaction->account_id);
        $phone = ltrim($account['phone'], '+');

        $date = $event->subscription->created_at->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));
        $end_date = $event->subscription->created_at->addMonths($event->subscription->subscriptionType->duration)
            ->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        $nf = new NumberFormatter('en', NumberFormatter::ORDINAL);
        $limit = $nf->format($type->level_limit);

        switch($type->duration) {
            case 1:
                $message = "Congratulations! You have successfully registered as a {$type->title} on {$date}, valid until {$end_date}. ";
                $message .= "You will earn commissions on every airtime purchased by your referred customers and subagents up to your {$limit} ripple.\n";
                break;
            default:
                $level_duration = $type->duration . " MONTHS";
                $message = "Congratulations! You have successfully preregistered as a {$type->title} on {$date}, valid until {$end_date}. ";
                $message .= "You will earn commissions on every airtime purchased by your referred customers and subagents up to your ";
                $message .= "{$limit} ripple, for {$level_duration} WITHOUT PAYING MONTHLY SUBSCRIPTION FEES.\n";
        }

        $message .= config('services.sidooh.tagline');

        SidoohNotify::notify([$phone], $message, EventType::SUBSCRIPTION_PAYMENT);
    }
}
