<?php

namespace Database\Seeders;

use App\Models\EarningAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class EarningAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        EarningAccount::truncate();
        Schema::enableForeignKeyConstraints();

        $accounts = [
            [
                'type'          => 'SYSTEM',
                'self_amount'   => 0,
                'invite_amount' => 0,
                'account_id'    => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

//        foreach (EarningAccountType::cases() as $accountType) {
//            $amount = $accountType == EarningAccountType::WITHDRAWALS ? 0 : 100;
//            $accounts[] = [
//                'type' => $accountType->name,
//                'self_amount' => $amount * .8,
//                'invite_amount' => $amount,
//                'account_id' => 1
//            ];
//        }

        EarningAccount::insert($accounts);
//        EarningAccount::factory(100)->create();
    }
}
