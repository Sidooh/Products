<?php

namespace Database\Factories;

use App\Enums\EarningCategory;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class CashbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $types = array_map(fn(EarningCategory $type) => $type->name, EarningCategory::cases());

        return [
            "account_id"     => fn(array $attributes) => match ($attributes["type"]) {
                EarningCategory::SYSTEM => null,
                default => $this->faker->randomElement([45, 46, 12, 47, 44]),
            },
            "transaction_id" => Transaction::factory(),
            "amount"         => $this->faker->randomElement([1.2, 1.8, 2.4, 3.0, 4.2, 6.0]),
            "type"           => $this->faker->randomElement($types)
        ];
    }
}
