<?php

namespace Database\Factories;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'account_id'           => $this->faker->numberBetween(1, 11),
            'subscription_type_id' => 1,
            'status'               => $this->faker->randomElement([
                Status::EXPIRED,
                Status::ACTIVE,
                Status::ACTIVE,
                Status::ACTIVE,
            ]),
            'start_date' => $this->faker->dateTimeBetween('-2 years'),
            'end_date'   => fn(array $attrs) => strtotime('+1 month', $attrs['start_date']->getTimestamp()),
        ];
    }
}
