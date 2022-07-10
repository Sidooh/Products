<?php

namespace Database\Seeders;

use App\Enums\EarningAccountType;
use App\Models\EarningAccount;
use Illuminate\Database\Seeder;

class EarningAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $accounts = [];

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
