<?php

namespace Database\Factories;

use App\Enums\EarningCategory;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashbackFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'account_id' => fn(array $attributes) => match ($attributes['type']) {
                EarningCategory::SYSTEM => null,
                default                 => $this->faker->numberBetween(1, 9),
            },
            'transaction_id' => fn(array $attrs) => Transaction::factory(state: [
                'account_id' => match ($attrs['type']) {
                    EarningCategory::SYSTEM => $this->faker->numberBetween(1, 9),
                    default                 => $attrs['account_id'],
                },
                'product_id' => $this->faker->randomElement([
                    ProductType::AIRTIME,
                    ProductType::UTILITY,
                    ProductType::SUBSCRIPTION,
                ]),
            ]),
            'amount' => $this->faker->randomElement([1.2, 1.8, 2.4, 3.0, 4.2, 6.0]),
            'type'   => $this->faker->randomElement(EarningCategory::cases()),
            'status' => fn(array $attrs) => match ($attrs['type']) {
                EarningCategory::SYSTEM => Status::PENDING,
                default                 => $this->faker->randomElement([
                    Status::COMPLETED,
                    Status::COMPLETED,
                    Status::COMPLETED,
                    Status::PENDING,
                ])
            },
        ];
    }
}
