<?php

namespace Database\Seeders;

use App\Models\Cashback;
use Illuminate\Database\Seeder;

class CashbackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Cashback::factory(10)->create();
    }
}
