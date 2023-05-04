<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*DB::table("users")->insert([
            "id"                => 7,
            "name"              => "Lil Nabz",
            "username"          => "Nabcellent",
            "email"             => "nabcellent.dev@gmail.com",
            "email_verified_at" => now(),
            "password"          => Hash::make(12345678)
        ]);
        DB::table("accounts")->insert([
            "id"       => 7,
            "phone"    => 254110039317,
            "active"   => true,
            "telco_id" => 1,
            "user_id"  => 7,
        ]);*/

        $this->call([
            //            SubscriptionTypeSeeder::class,
            //            ProductSeeder::class,
            //            EarningAccountSeeder::class,
            TransactionSeeder::class,
            CashbackSeeder::class,
            SubscriptionSeeder::class,
        ]);
    }
}
