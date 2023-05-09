<?php

namespace Database\Seeders;

use App\Models\Cashback;
use Illuminate\Database\Seeder;

class CashbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cashback::factory(50)->create();
    }
}
