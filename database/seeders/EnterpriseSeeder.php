<?php

namespace Database\Seeders;

use App\Enums\EnterpriseAccountType;
use App\Models\Enterprise;
use Illuminate\Database\Seeder;

class EnterpriseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $enterprise = Enterprise::create([
            "name"     => "Walmart",
            "settings" => [
                ["type" => "lunch", "max" => 2000],
                ["type" => "general", "max" => 5000],
            ]
        ]);

        $enterprise->enterpriseAccounts()->create(['account_id' => 46, 'type' => EnterpriseAccountType::EMPLOYEE]);
        $enterprise->floatAccount()->create(['balance' => 100000]);
    }
}
