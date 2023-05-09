<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
//        Schema::disableForeignKeyConstraints();
//        Transaction::truncate();
//        Payment::truncate();
//        Schema::enableForeignKeyConstraints();

        Transaction::factory(100)->hasPayment()->create();
    }
}
