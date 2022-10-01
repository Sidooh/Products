<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        //        Schema::disableForeignKeyConstraints();
//        Estate::truncate();
//        Schema::enableForeignKeyConstraints();

        Transaction::factory(5)->hasPayment()->create();
    }
}
