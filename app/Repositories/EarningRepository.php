<?php

namespace App\Repositories;

use App\Models\Earning;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EarningRepository
{
    public static function calcEarnings(Transaction $transaction, float $earnings)
    {
        Log::info("--- --- --- --- ---   ...[EARNING REPOSITORY]: Calc Earnings($earnings)...   --- --- --- --- ---");

        $acc = $transaction->account;

        $groupEarnings = round($earnings * config('services.sidooh.earnings.users_percentage'), 4);
        $userEarnings = round($groupEarnings / 6, 4);

        $totalLeftOverEarnings = $groupEarnings;

        if ($transaction->amount >= 20 || $transaction->product_id == 4) {
            if ($acc->isRoot()) {
                $e = Earning::create([
                    'account_id' => $acc->id,
                    'transaction_id' => $transaction->id,
                    'earnings' => $userEarnings,
                    'type' => 'SELF',
                ]);

                $sub_acc = $acc->current_account;
                $sub_acc2 = $acc->savings_account;

                $sub_acc->in += .2 * $userEarnings;
                $sub_acc2->in += .8 * $userEarnings;

                $sub_acc->save();
                $sub_acc2->save();

                $totalLeftOverEarnings -= $userEarnings;
            } else {
                $referrals = (new AccountRepository)->subscribed_nth_level_referrers($acc, 5, false);

                if (count($referrals) + 1 > 6) {
                    abort(500);
                }

                $now = Carbon::now('utc')->toDateTimeString();

                $userEarning = [
                    [
                        'account_id' => $acc->id,
                        'transaction_id' => $transaction->id,
                        'earnings' => $userEarnings,
                        'type' => 'SELF',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];

                $totalLeftOverEarnings -= $userEarnings;

                foreach ($referrals as $referral) {
                    $userEarning[] = [
                        'account_id' => $referral->id,
                        'transaction_id' => $transaction->id,
                        'earnings' => $userEarnings,
                        'type' => 'REFERRAL',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $totalLeftOverEarnings -= $userEarnings;
                }

                Earning::insert($userEarning);

                foreach ($userEarning as $ue) {
//                    TODO: Get all accounts at once then filter programmatically
                    $acc = SubAccount::type('CURRENT')->whereAccountId($ue['account_id'])->first();
                    $acc2 = SubAccount::type('SAVINGS')->whereAccountId($ue['account_id'])->first();

                    $acc->in += .2 * $userEarnings;
                    $acc2->in += .8 * $userEarnings;

                    $acc->save();
                    $acc2->save();
                }
            }

            $now = Carbon::now('utc')->toDateTimeString();

            $systemEarnings = [
                [
                    //  'account_id' => $acc->id,
                    'transaction_id' => $transaction->id,
                    'earnings' => $earnings - $groupEarnings,
                    'type' => 'SYSTEM',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ];

            if ($totalLeftOverEarnings > 0) {
                $systemEarnings[] = [
                    //  'account_id' => $referral->id,
                    'transaction_id' => $transaction->id,
                    'earnings' => $totalLeftOverEarnings,
                    'type' => 'SYSTEM',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Earning::insert($systemEarnings);

            $transaction->status = 'completed';
            $transaction->save();
        }
    }

    public static function getPointsEarned(float $discount): string
    {
        $e = $discount * config('services.sidooh.earnings.users_percentage');

        return 'KES'.$e / 6;
    }
}
