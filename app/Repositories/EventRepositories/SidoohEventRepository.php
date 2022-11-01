<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use NumberFormatter;

class SidoohEventRepository extends EventRepository
{
    /**
     * @throws Exception
     */
    public static function subscriptionPurchaseSuccess(Subscription $subscription, Transaction $transaction)
    {
        $type = $subscription->subscriptionType;
        $account = SidoohAccounts::find($transaction->account_id);
        $phone = ltrim($account['phone'], '+');

        $date = $subscription->created_at->timezone('Africa/Nairobi')->format(config('settings.sms_date_time_format'));
        $end_date = $subscription->created_at->addMonths($subscription->subscriptionType->duration)
            ->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        $nf = new NumberFormatter('en', NumberFormatter::ORDINAL);
        $limit = $nf->format($type->level_limit);

        switch($type->duration) {
            case 1:
                $message = "Congratulations! You have successfully registered as a {$type->title} on {$date}, valid until {$end_date}. ";
                $message .= "You will earn commissions on every airtime purchased by your referred customers and sub-agents up to your {$limit} ripple.\n";
                break;
            default:
                $level_duration = $type->duration.' MONTHS';
                $message = "Congratulations! You have successfully pre-registered as a {$type->title} on {$date}, valid until {$end_date}. ";
                $message .= 'You will earn commissions on every airtime purchased by your referred customers and sub-agents up to your ';
                $message .= "{$limit} ripple, for {$level_duration} WITHOUT PAYING MONTHLY SUBSCRIPTION FEES.\n";
        }

        $message .= config('services.sidooh.tagline');

        SidoohNotify::notify([$phone], $message, EventType::SUBSCRIPTION_PAYMENT);
    }
}
