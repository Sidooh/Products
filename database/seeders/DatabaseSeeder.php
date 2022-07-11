<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            SubscriptionTypeSeeder::class,
            ProductSeeder::class,
            EarningAccountSeeder::class,
//            CashbackSeeder::class,
//            EnterpriseSeeder::class,
        ]);
    }
}
