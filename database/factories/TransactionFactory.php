<?php

namespace Database\Factories;

use App\Enums\Description;
use App\Enums\Initiator;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Services\SidoohPayments;
use App\Services\SidoohSavings;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'account_id' => $this->faker->numberBetween(1, 9),
            'product_id' => $this->faker->randomElement([
                ProductType::AIRTIME,
                ProductType::MERCHANT,
                ProductType::UTILITY,
                ProductType::VOUCHER,
                ProductType::SUBSCRIPTION,
                ProductType::WITHDRAWAL,
            ]),
            'initiator' => Initiator::CONSUMER,
            'type'      => fn(array $attributes) => match ($attributes['product_id']) {
                ProductType::AIRTIME, ProductType::MERCHANT, ProductType::UTILITY, ProductType::SUBSCRIPTION, ProductType::VOUCHER => TransactionType::PAYMENT,
                ProductType::WITHDRAWAL => TransactionType::WITHDRAWAL
            },
            'amount' => fn(array $attributes) => match ($attributes['product_id']) {
                ProductType::MERCHANT, ProductType::WITHDRAWAL, ProductType::UTILITY, ProductType::VOUCHER => $this->faker->numberBetween(20, 10000),
                ProductType::AIRTIME      => $this->faker->numberBetween(20, 3000),
                ProductType::SUBSCRIPTION => 395,
                default                   => $this->faker->numberBetween(20, 100)
            },
            'charge' => fn(array $attributes) => match ($attributes['product_id']) {
                ProductType::MERCHANT   => SidoohPayments::getBuyGoodsCharge($attributes['amount']),
                ProductType::WITHDRAWAL => SidoohSavings::getWithdrawalCharge($attributes['amount']),
                default                 => 0
            },
            'description' => fn(array $attributes) => match ($attributes['product_id']) {
                ProductType::AIRTIME      => Description::AIRTIME_PURCHASE,
                ProductType::MERCHANT     => Description::MERCHANT_PAYMENT,
                ProductType::UTILITY      => Description::UTILITY_PURCHASE,
                ProductType::VOUCHER      => Description::VOUCHER_PURCHASE,
                ProductType::SUBSCRIPTION => Description::SUBSCRIPTION_PURCHASE,
                ProductType::WITHDRAWAL   => Description::EARNINGS_WITHDRAWAL
            },
            'destination' => fn(array $attributes) => match ($attributes['product_id']) {
                ProductType::UTILITY, ProductType::MERCHANT => $this->faker->randomNumber(6),
                default => $this->faker->regexify('/(254){1}[7]{1}([0-2]{1}[0-9]{1}|[9]{1}[0-2]{1})[0-9]{6}/')
            },
            'status' => $this->faker->randomElement([
                Status::COMPLETED,
                Status::COMPLETED,
                Status::COMPLETED,
                Status::COMPLETED,
                Status::COMPLETED,
                Status::PENDING,
                Status::REFUNDED,
                Status::FAILED,
            ]),
            'created_at' => $this->faker->dateTimeBetween('-1 years'),
        ];
    }
}
