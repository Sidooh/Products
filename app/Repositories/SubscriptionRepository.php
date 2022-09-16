<?php

namespace App\Repositories;

use App\Enums\EventType;
use App\Enums\Status;
use App\Models\Subscription;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionRepository
{
    public static function checkExpiry(): array
    {
        // TODO: What if end_date order is not id order? 1,2022-07-07 12:02:59; 2,2022-07-04 12:02:59 ...?

        // Get latest IDs
        $latestSubscriptions = Subscription::select('account_id', DB::raw('MAX(id) as latest_subscription_id'))
            ->includePreExpiry()->orWhere->includePostExpiry()->groupBy('account_id');

        // Get subscription data based on latest IDs sub query
        $requiredSubcriptions = Subscription::joinSub($latestSubscriptions, 'latest_subs', fn($join) => $join->on('id', '=', 'latest_subs.latest_subscription_id'))
            ->get();

        [$pastSubs, $futureSubs] = $requiredSubcriptions->partition(fn($s) => $s->end_date < now());

        $expiredSubs = collect();

        $pastSubs->each(function($sub) use ($expiredSubs) {
            $daysPast = now()->diffInDays($sub->end_date);
            $sub['x'] = $daysPast;
            $sub['y'] = $sub->end_date->diffForHumans();

            if($sub->status !== Status::EXPIRED) {
                $expiredSubs->add($sub);
            }

            if(in_array($daysPast, [2, 3, 4, 6])) {
                //notify expired...

                $message = "Subscription expired {$sub->end_date->diffForHumans()}\n\n";
                $message .= config('services.sidooh.tagline');

                $account = SidoohAccounts::find($sub->account_id);
                SidoohNotify::notify([$account['phone']], $message, EventType::SUBSCRIPTION_EXPIRY);
            }
        });

        $futureSubs->each(function($sub) {
            $daysLeft = now()->diffInDays($sub->end_date);
            $sub['x'] = $daysLeft;
            $sub['y'] = $sub->end_date->diffForHumans();

            if(in_array($daysLeft, [5, 3, 2, 1])) {
                //notify expiry due...

                $message = "Subscription will expire {$sub->end_date->diffForHumans()}\n\n";
                $message .= config('services.sidooh.tagline');

                $account = SidoohAccounts::find($sub->account_id);
                SidoohNotify::notify([$account['phone']], $message, EventType::SUBSCRIPTION_EXPIRY);
            }
        });

        Log::info('...[SUB CTRL]... Subs: ', [now(), $pastSubs->toArray(), $futureSubs->toArray()]);

        $adminMsg = "STATUS:SUBSCRIPTIONS\n\n";
        $adminMsg .= "Processed: {$latestSubscriptions->count()}";
        $adminMsg .= "\nUpcoming: {$futureSubs->count()}";

        // Process expired Subs
        if($expiredCount = $expiredSubs->count()) {
            $expiredSubsAccs = collect();

            $expiredSubs->each(function($sub) use ($expiredSubsAccs) {
                $account = SidoohAccounts::find($sub->account_id);
                $expiredSubsAccs->add($account);
            });

            Subscription::whereIn('id', $expiredSubs->pluck('id'))->update(['status' => Status::EXPIRED]);

            $message = "Your subscription to Sidooh has expired.\n\n";
            $message .= "Dial *384*99# NOW for FREE on your Safaricom line to renew your subscription and continue to ";
            $message .= "earn commissions on airtime and tokens purchased by your invited friends and sub-agents";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify($expiredSubsAccs->pluck('phone')->toArray(), $message, EventType::SUBSCRIPTION_EXPIRY);
            $adminMsg .= "\nExpired: $expiredCount";
        }


        // Notify admin
        SidoohNotify::notify(admin_contacts(), $adminMsg, EventType::STATUS_UPDATE);


        return [$pastSubs, $futureSubs, $expiredSubs];
    }
}
