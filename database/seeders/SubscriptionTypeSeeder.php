<?php

namespace Database\Seeders;

use App\Models\SubscriptionType;
use Illuminate\Database\Seeder;

class SubscriptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'title'       => 'Sidooh Aspiring Agent',
                'price'      => 365,
                'active'      => 1,
                'level_limit' => 3,
                'created_at'  => now(),
                'updated_at'  => now()
            ],
            [
                'title'       => 'Sidooh Thriving Agent',
                'price'      => 975,
                'active'      => 1,
                'level_limit' => 5,
                'created_at'  => now(),
                'updated_at'  => now()
            ],
        ];

        SubscriptionType::insert($types);

        $types = [
            [
                'title'       => 'Sidooh Aspiring Agent',
                'price'      => 4275,
                'active'      => 1,
                'level_limit' => 3,
                'duration'    => 12,
                'created_at'  => now(),
                'updated_at'  => now()
            ],
            [
                'title'       => 'Sidooh Thriving Agent',
                'price'      => 8775,
                'active'      => 1,
                'level_limit' => 5,
                'duration'    => 12,
                'created_at'  => now(),
                'updated_at'  => now()
            ],
        ];

        SubscriptionType::insert($types);
    }
}
