<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EarningAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
<<<<<<< HEAD
        $accounts = [];
=======
        //
        $accounts = [
            [
                'type' => 'SYSTEM',
                'self_amount' => 0,
                'invite_amount' => 0,
                'account_id' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
>>>>>>> 0053468 (fixes product types and transactions relations)

        foreach (EarningAccountType::cases() as $accountType) {
            $amount = $accountType == EarningAccountType::WITHDRAWALS ? 0 : 100;
            $accounts[] = [
                'type' => $accountType->name,
                'self_amount' => $amount * .8,
                'invite_amount' => $amount,
                'account_id' => 1
            ];
        }

        EarningAccount::insert($accounts);
    }
}
