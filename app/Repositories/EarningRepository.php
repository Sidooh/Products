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
    public static function calculateEarnings(Transaction $transaction, float $earnings): void
    {
        Log::info("--- --- --- --- ---   ...[EARNING REPOSITORY]: Calc Earnings($earnings)...   --- --- --- --- ---");

        $account = SidoohAccounts::find($transaction->account_id);

        if ($transaction->product_id == ProductType::SUBSCRIPTION->value) {
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

        return 'KES' . $e / 6;
    }

    private static function computeSubscriptionEarnings(array $account, Transaction $transaction): void
    {
        $earningPerUser = config('services.sidooh.earnings.subscription.cashback', 35);

        if (isset($account['inviter_id'])) {
            self::computeSubscriptionEarningsForSelf($transaction, $earningPerUser);

            // Account is invited
            $inviters = SidoohAccounts::getInviters($transaction->account_id);
            array_shift($inviters);

            foreach ($inviters as $inviter) {
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
                    'account_id' => $transaction->account_id,
                    'type' => EarningAccountType::SUBSCRIPTION->name
                ]);

                $earningAccount->invite_amount += $earningPerUser;
                $earningAccount->save();

                // Send details to savings service
                // TODO: savings service
            }
        } else {
            // Account is root

            self::computeSubscriptionEarningsForSelf($transaction, $earningPerUser);

        }

    }

    private static function computeSubscribedAccountEarnings(array $account, Transaction $transaction, float $earnings): void
    {
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
            'type' => EarningAccountType::PURCHASE->name
        ]);

        $earningAccount->self_amount += $rootEarnings;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service

        if (isset($account['inviter_id'])) {
            // Account is invited
            $inviters = SidoohAccounts::getInviters($transaction->account_id);
            array_shift($inviters);

            $groupEarnings = round($transaction->amount * config('services.sidooh.earnings.subscribed_inviters_percentage', .02), 4);
            $userEarnings = round($groupEarnings / 5, 4);

            foreach ($inviters as $inviter) {
                // Create Earning Transaction
                Cashback::create([
                    'account_id' => $inviter['id'],
                    'transaction_id' => $transaction->id,
                    'amount' => $userEarnings,
                    'type' => EarningCategory::INVITE->name
                ]);

                // Update Earning Account
                //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                $earningAccount = EarningAccount::firstOrCreate([
                    'account_id' => $transaction->account_id,
                    'type' => EarningAccountType::PURCHASE->name
                ]);

                $earningAccount->invite_amount += $userEarnings;
                $earningAccount->save();

                // Send details to savings service
                // TODO: savings service
            }

        }

    }

    private static function computeAccountEarnings(array $account, Transaction $transaction, float $earnings): void
    {
        $groupEarnings = round($earnings * config('services.sidooh.earnings.users_percentage', .6), 4);
        $userEarnings = round($groupEarnings / 6, 4);

        $totalLeftOverEarnings = $groupEarnings;

        if ($transaction->amount >= 20) {
            if (!isset($account['inviter_id'])) {
                self::computeAccountEarningsForSelf($transaction, $userEarnings);
                $totalLeftOverEarnings -= $userEarnings;

            } else {
                // Account is invited
                self::computeAccountEarningsForSelf($transaction, $userEarnings);
                $totalLeftOverEarnings -= $userEarnings;

                $inviters = SidoohAccounts::getInviters($transaction->account_id);
                array_shift($inviters);

                if (count($inviters) + 1 > 6) abort(500);

                foreach ($inviters as $inviter) {
                    // Create Earning Transaction
                    Cashback::create([
                        'account_id' => $inviter['id'],
                        'transaction_id' => $transaction->id,
                        'amount' => $userEarnings,
                        'type' => EarningCategory::INVITE->name
                    ]);

                    // Update Earning Account
                    //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                    $earningAccount = EarningAccount::firstOrCreate([
                        'account_id' => $transaction->account_id,
                        'type' => EarningAccountType::PURCHASE->name
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
            'type' => EarningAccountType::SUBSCRIPTION->name
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
            'type' => EarningAccountType::PURCHASE->name
        ]);

        $earningAccount->self_amount += $userEarnings;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service
    }


}
