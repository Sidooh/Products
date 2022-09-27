<?php

namespace Database\Factories;

use App\Enums\Description;
use App\Enums\Initiator;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'account_id'  => $this->faker->randomElement([12, 44, 45, 46, 47]),
            'product_id'  => fn(array $attributes) => match ($attributes['description']) {
                Description::AIRTIME_PURCHASE => ProductType::AIRTIME,
                Description::UTILITY_PURCHASE => ProductType::UTILITY,
                Description::VOUCHER_PURCHASE => ProductType::VOUCHER,
                Description::SUBSCRIPTION_PURCHASE => ProductType::SUBSCRIPTION
            },
            'initiator'   => Initiator::CONSUMER,
            'type'        => TransactionType::CREDIT,
            'amount'      => $this->faker->numberBetween(20, 100),
            'description' => $this->faker->randomElement([
                Description::AIRTIME_PURCHASE,
                Description::UTILITY_PURCHASE,
                Description::VOUCHER_PURCHASE,
                Description::SUBSCRIPTION_PURCHASE,
            ]),
            'status'      => $this->faker->randomElement([
                Status::COMPLETED,
                Status::PENDING,
                Status::REFUNDED,
                Status::FAILED,
            ]),
            'created_at'  => $this->faker->dateTimeBetween('-1 years'),
        ];
    }
}
