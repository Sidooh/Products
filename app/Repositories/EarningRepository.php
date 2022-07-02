<?php

namespace App\Repositories;

use App\Enums\EarningAccountType;
use App\Enums\EarningCategory;
use App\Enums\ProductType;
use App\Models\Cashback;
use App\Models\EarningAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EarningRepository
{
    /**
     * @throws \Exception
     */
    public static function calculateEarnings(Transaction $transaction, float $earnings): void
    {
        Log::info("...[EARNING REPOSITORY]: Calculate Earnings($earnings)...");

        $account = SidoohAccounts::find($transaction->account_id);

        if ($transaction->product_id == ProductType::SUBSCRIPTION) {
            self::computeSubscriptionEarnings($account, $transaction);
            return;
        }

        $hasActiveSubscription = Subscription::active($transaction->account_id);

        if ($hasActiveSubscription) {
            self::computeSubscribedAccountEarnings($account, $transaction, $earnings);
        } else {
            self::computeAccountEarnings($account, $transaction, $earnings);
        }
    }

    public static function getPointsEarned(float $discount): string
    {
        $e = $discount * config('services.sidooh.earnings.users_percentage', .6);

        return 'Ksh' . $e / 6;
    }

    /**
     * @throws \Exception
     */
    private static function computeSubscriptionEarnings(array $account, Transaction $transaction): void
    {
        Log::info("...[EARNING REPOSITORY]: Compute Subscription Earnings...");

        $earningPerUser = config('services.sidooh.earnings.subscription.cashback', 35);

        self::computeSubscriptionEarningsForSelf($transaction, $earningPerUser);

        if (isset($account['inviter_id'])) {
            // Account is invited
            $inviters = SidoohAccounts::getInviters($transaction->account_id);
            array_shift($inviters);

            if (count($inviters) + 1 > 6) abort(500, "Too many inviters");

            foreach ($inviters as $inviter) {
                $hasActiveSubscription = Subscription::active($inviter['id']);
                $isLevelOneInviter = $inviter['level'] == 1;
                if (!$hasActiveSubscription && !$isLevelOneInviter) continue;

                // Create Earning Transaction
                Cashback::create([
                    'account_id' => $inviter['id'],
                    'transaction_id' => $transaction->id,
                    'amount' => $earningPerUser,
                    'type' => EarningCategory::INVITE->name
                ]);

                // Update Earning Account
                //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                $earningAccount = EarningAccount::firstOrCreate([
                    'account_id' => $inviter['id'],
                    'type' => EarningAccountType::SUBSCRIPTIONS->name
                ]);

                $earningAccount->invite_amount += $earningPerUser;
                $earningAccount->save();

                // Send details to savings service
                // TODO: savings service
            }
        }
    }

    /**
     * @throws \Exception
     */
    private static function computeSubscribedAccountEarnings(array $account, Transaction $transaction, float $earnings): void
    {
        Log::info("...[EARNING REPOSITORY]: Compute Subscribed Acc Earnings...");

        $rootEarnings = round($earnings * config('services.sidooh.earnings.subscribed_users_percentage', 1), 4);

        // Create Earning Transaction
        Cashback::create([
            'account_id' => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount' => $rootEarnings,
            'type' => EarningCategory::SELF->name
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type' => EarningAccountType::PURCHASES->name
        ]);

        $earningAccount->self_amount += $rootEarnings;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service

        if (isset($account['inviter_id'])) {
            // Account is invited
            $inviters = SidoohAccounts::getInviters($transaction->account_id);
            array_shift($inviters);

            if (count($inviters) + 1 > 6) abort(500, "Too many inviters");

            $groupEarnings = round($transaction->amount * config('services.sidooh.earnings.subscribed_inviters_percentage', .02), 4);
            $userEarnings = round($groupEarnings / 5, 4);

            foreach ($inviters as $inviter) {
                $hasActiveSubscription = Subscription::active($inviter['id']);
                $isLevelOneInviter = $inviter['level'] == 1;
                if (!$hasActiveSubscription && !$isLevelOneInviter) continue;

                // Create Earning Transaction
                Cashback::create([
                    "account_id" => $inviter['id'],
                    "transaction_id" => $transaction->id,
                    "amount" => $userEarnings,
                    "type" => EarningCategory::INVITE->name
                ]);

                // Update Earning Account
                //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                $earningAccount = EarningAccount::firstOrCreate([
                    'account_id' => $inviter['id'],
                    'type' => EarningAccountType::PURCHASES->name
                ]);

                $earningAccount->invite_amount += $userEarnings;
                $earningAccount->save();

                // Send details to savings service
                // TODO: savings service
            }

        }

    }

    /**
     * @throws \Exception
     */
    private static function computeAccountEarnings(array $account, Transaction $transaction, float $earnings): void
    {
        Log::info("...[EARNING REPOSITORY]: Compute Earnings...");

        $groupEarnings = round($earnings * config('services.sidooh.earnings.users_percentage', .6), 4);
        $userEarnings = round($groupEarnings / 6, 4);

        $totalLeftOverEarnings = $groupEarnings;

        if ($transaction->amount >= 20) {
            self::computeAccountEarningsForSelf($transaction, $userEarnings);
            $totalLeftOverEarnings -= $userEarnings;

            if (isset($account['inviter_id'])) {
                // Account is invited

                $inviters = SidoohAccounts::getInviters($transaction->account_id);
                array_shift($inviters);

                if(count($inviters) + 1 > 6) abort(500, "Too many inviters");

                foreach($inviters as $inviter) {
                    $hasActiveSubscription = Subscription::active($inviter['id']);
                    $isLevelOneInviter = $inviter['level'] == 1;
                    if(!$hasActiveSubscription && !$isLevelOneInviter) continue;

                    // Create Earning Transaction
                    Cashback::create([
                        'account_id'     => $inviter['id'],
                        'transaction_id' => $transaction->id,
                        'amount'         => $userEarnings,
                        'type'           => EarningCategory::INVITE->name
                    ]);

                    // Update Earning Account
                    //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                    $earningAccount = EarningAccount::firstOrCreate([
                        'account_id' => $inviter['id'],
                        'type' => EarningAccountType::PURCHASES->name
                    ]);

                    $earningAccount->invite_amount += $userEarnings;
                    $earningAccount->save();

                    // Send details to savings service
                    // TODO: savings service

                    $totalLeftOverEarnings -= $userEarnings;
                }
            }

            $now = Carbon::now('utc')->toDateTimeString();

            $systemEarnings = [
                [
                    'transaction_id' => $transaction->id,
                    'amount' => $earnings - $groupEarnings,
                    'type' => EarningCategory::SYSTEM->name,
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            ];

            if ($totalLeftOverEarnings > 0) {
                $systemEarnings[] = [
                    'transaction_id' => $transaction->id,
                    'amount' => $totalLeftOverEarnings,
                    'type' => EarningCategory::SYSTEM->name,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            Cashback::insert($systemEarnings);
        }
    }

    private static function computeSubscriptionEarningsForSelf(Transaction $transaction, float $earningPerUser): void
    {
        // Create Earning Transaction
        Cashback::create([
            'account_id' => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount' => $earningPerUser,
            'type' => EarningCategory::SELF->name
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type' => EarningAccountType::SUBSCRIPTIONS->name
        ]);

        $earningAccount->self_amount += $earningPerUser;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service
    }

    private static function computeAccountEarningsForSelf(Transaction $transaction, float $userEarnings): void
    {
        // Create Earning Transaction
        Cashback::create([
            'account_id' => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount' => $userEarnings,
            'type' => EarningCategory::SELF->name
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type' => EarningAccountType::PURCHASES->name
        ]);

        $earningAccount->self_amount += $userEarnings;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service
    }


}
