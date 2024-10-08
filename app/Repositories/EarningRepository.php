<?php

namespace App\Repositories;

use App\Enums\EarningAccountType;
use App\Enums\EarningCategory;
use App\Enums\EventType;
use App\Enums\ProductType;
use App\Models\Cashback;
use App\Models\EarningAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EarningRepository
{
    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public static function calculateEarnings(Transaction $transaction, float $earnings): void
    {
        Log::info("...[REP - EARNING]: Calculate Earnings($earnings)...");

        // Ensure earnings have not already been allocated
        if (Cashback::whereTransactionId($transaction->id)->exists()) {
            SidoohNotify::notify([
                '254714611696',
            ], "ERROR:EARNINGS\n$transaction->id", EventType::ERROR_ALERT);
            Log::critical('Possible duplicate earnings calculation... Confirm!!!');
            exit;
        }

        $account = SidoohAccounts::find($transaction->account_id);

        // TODO: What happens if this fails? Do we get notified? Does it fail silently?
        DB::transaction(function() use ($earnings, $account, $transaction) {
            if ($transaction->product_id == ProductType::SUBSCRIPTION) {
                self::computeSubscriptionEarnings($account, $transaction);

                return;
            }

            if ($transaction->product_id == ProductType::MERCHANT) {
                self::computeMerchantEarnings($transaction, $earnings);

                return;
            }

            $hasActiveSubscription = Subscription::active($transaction->account_id);

            if ($hasActiveSubscription) {
                self::computeSubscribedAccountEarnings($account, $transaction, $earnings);
            } else {
                self::computeAccountEarnings($account, $transaction, $earnings);
            }
        });
    }

    public static function getPointsEarned(Transaction $transaction, float $discount): string
    {
        if (Subscription::active($transaction->account_id)) {
            $rootEarnings = round($discount * config('services.sidooh.earnings.subscribed_users_percentage', 1), 4);
        } else {
            $rootEarnings = round($discount * config('services.sidooh.earnings.users_percentage', .6) / 6, 4);
        }

        return 'Ksh'.$rootEarnings;
    }

    /**
     * @throws \Exception
     */
    private static function computeSubscriptionEarnings(array $account, Transaction $transaction): void
    {
        Log::info('...[REP - EARNING]: Compute Subscription Earnings...');

        $earningPerUser = config('services.sidooh.earnings.subscription.cashback', 35);

        self::computeSubscriptionEarningsForSelf($transaction, $earningPerUser);

        if (isset($account['inviter_id'])) {
            // Account is invited
            $inviters = SidoohAccounts::getInviters($transaction->account_id);
            array_shift($inviters);

            if (count($inviters) + 1 > 6) {
                abort(500, 'Too many inviters');
            }

            $notifications = [];

            foreach ($inviters as $inviter) {
                $hasActiveSubscription = Subscription::active($inviter['id']);
                $isLevelOneInviter = $inviter['level'] == 1;
                if (! $hasActiveSubscription && ! $isLevelOneInviter) {
                    continue;
                }

                // Create Earning Transaction
                Cashback::create([
                    'account_id'     => $inviter['id'],
                    'transaction_id' => $transaction->id,
                    'amount'         => $earningPerUser,
                    'type'           => EarningCategory::INVITE->value,
                ]);

                // Update Earning Account
                //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                $earningAccount = EarningAccount::firstOrCreate([
                    'account_id' => $inviter['id'],
                    'type'       => EarningAccountType::SUBSCRIPTIONS,
                ]);

                $earningAccount->invite_amount += $earningPerUser;
                $earningAccount->save();

                $notifications[] = [
                    'phone'  => $inviter['phone'],
                    'level'  => $inviter['level'],
                    'amount' => $earningPerUser,
                ];

                // Send details to savings service
                // TODO: savings service
            }

            // TODO: Can we have bulk notify endpoint?
            foreach ($notifications as $notification) {
                $message = 'Congratulations! ';
                $message .= "One of your ripple invites at level {$notification['level']} has recently subscribed as a Sidooh Agent.\n\n";
                $message .= config('services.sidooh.tagline');

                SidoohNotify::notify([$notification['phone']], $message, EventType::SUBSCRIPTION_PAYMENT);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private static function computeSubscribedAccountEarnings(array $account, Transaction $transaction, float $earnings): void
    {
        Log::info('...[REP - EARNING]: Compute Subscribed Acc Earnings...');

        $rootEarnings = round($earnings * config('services.sidooh.earnings.subscribed_users_percentage', 1), 4);

        // Create Earning Transaction
        Cashback::create([
            'account_id'     => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount'         => $rootEarnings,
            'type'           => EarningCategory::SELF_EARNING->value,
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type'       => EarningAccountType::PURCHASES,
        ]);

        $earningAccount->self_amount += $rootEarnings;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service

        if (isset($account['inviter_id'])) {
            // Account is invited
            $inviters = SidoohAccounts::getInviters($transaction->account_id);
            array_shift($inviters);

            if (count($inviters) + 1 > 6) {
                abort(500, 'Too many inviters');
            }

            $groupEarnings = round($transaction->amount * config('services.sidooh.earnings.subscribed_inviters_percentage', .02), 4);
            $userEarnings = round($groupEarnings / 5, 4);

            foreach ($inviters as $inviter) {
                $hasActiveSubscription = Subscription::active($inviter['id']);
                $isLevelOneInviter = $inviter['level'] == 1;
                if (! $hasActiveSubscription && ! $isLevelOneInviter) {
                    continue;
                }

                // Create Earning Transaction
                Cashback::create([
                    'account_id'     => $inviter['id'],
                    'transaction_id' => $transaction->id,
                    'amount'         => $userEarnings,
                    'type'           => EarningCategory::INVITE->value,
                ]);

                // Update Earning Account
                //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                $earningAccount = EarningAccount::firstOrCreate([
                    'account_id' => $inviter['id'],
                    'type'       => EarningAccountType::PURCHASES,
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
        Log::info('...[REP - EARNING]: Compute Earnings...');

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

                if (count($inviters) + 1 > 6) {
                    abort(500, 'Too many inviters');
                }

                foreach ($inviters as $inviter) {
                    $hasActiveSubscription = Subscription::active($inviter['id']);
                    $isLevelOneInviter = $inviter['level'] == 1;
                    if (! $hasActiveSubscription && ! $isLevelOneInviter) {
                        continue;
                    }

                    // Create Earning Transaction
                    Cashback::create([
                        'account_id'     => $inviter['id'],
                        'transaction_id' => $transaction->id,
                        'amount'         => $userEarnings,
                        'type'           => EarningCategory::INVITE->value,
                    ]);

                    // Update Earning Account
                    //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
                    $earningAccount = EarningAccount::firstOrCreate([
                        'account_id' => $inviter['id'],
                        'type'       => EarningAccountType::PURCHASES,
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
                    'amount'         => $earnings - $groupEarnings,
                    'type'           => EarningCategory::SYSTEM->value,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ],
            ];

            if ($totalLeftOverEarnings > 0) {
                $systemEarnings[] = [
                    'transaction_id' => $transaction->id,
                    'amount'         => $totalLeftOverEarnings,
                    'type'           => EarningCategory::SYSTEM->value,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            Cashback::insert($systemEarnings);
        }
    }

    private static function computeMerchantEarnings(Transaction $transaction, float $earning): void
    {
        Log::info('...[REP - EARNING]: Compute Merchant Earnings...');

        // Create Earning Transaction
        Cashback::create([
            'account_id'     => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount'         => $earning,
            'type'           => EarningCategory::SELF_EARNING->value,
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type'       => EarningAccountType::MERCHANT,
        ]);

        $earningAccount->self_amount += $earning;
        $earningAccount->save();
    }

    private static function computeSubscriptionEarningsForSelf(Transaction $transaction, float $earningPerUser): void
    {
        // Create Earning Transaction
        Cashback::create([
            'account_id'     => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount'         => $earningPerUser,
            'type'           => EarningCategory::SELF_EARNING->value,
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type'       => EarningAccountType::SUBSCRIPTIONS,
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
            'account_id'     => $transaction->account_id,
            'transaction_id' => $transaction->id,
            'amount'         => $userEarnings,
            'type'           => EarningCategory::SELF_EARNING->value,
        ]);

        // Update Earning Account
        //TODO: Is it possible to update in one statement? with the addition since we don't know the initial amount?
        $earningAccount = EarningAccount::firstOrCreate([
            'account_id' => $transaction->account_id,
            'type'       => EarningAccountType::PURCHASES,
        ]);

        $earningAccount->self_amount += $userEarnings;
        $earningAccount->save();

        // Send details to savings service
        // TODO: savings service
    }
}
