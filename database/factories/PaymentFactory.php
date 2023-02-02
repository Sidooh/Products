<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'payment_id'     => $this->faker->unique()->randomNumber(),
            'transaction_id' => Transaction::factory(),
            'amount'         => $this->faker->numberBetween(20, 100),
            'type'           => $this->faker->randomElement(['MPESA', 'SIDOOH']),
            'subtype'        => fn (array $attributes) => match ($attributes['type']) {
                'MPESA'  => 'STK',
                'SIDOOH' => 'VOUCHER',
            },
        ];
    }
}
