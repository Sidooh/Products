<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohSavings extends SidoohService
{
    public static function baseUrl()
    {
        return config('services.sidooh.services.savings.url');
    }

    /**
     * @throws \Exception
     */
    public static function withdrawEarnings(Transaction $transaction, PaymentMethod $method): array
    {
        Log::info('...[SRV - SAVINGS]: Withdraw Earnings...');

        $url = self::baseUrl()."/accounts/$transaction->account_id/earnings/withdraw";

        $data = [
            'amount'              => $transaction->amount,
            'reference'           => "$transaction->id",
            'destination'         => $method->name,
            'destination_account' => $transaction->destination,
            'ipn'                 => config('app.url').'/api/sidooh/savings/callback',
        ];

        return parent::fetch($url, 'POST', $data);
    }

    /**
     * @throws \Exception
     */
    public static function save(array $savings)
    {
        Log::info('...[SRV - SAVINGS]: Save...');

        $url = self::baseUrl().'/accounts/earnings';

        return parent::fetch($url, 'POST', $savings);
    }

    public static function getWithdrawalCharge(int $amount): int
    {
        Log::info('...[SRV - SAVINGS]: Get Withdrawal Charge...', [$amount]);

        return Cache::remember("withdrawal_charge_$amount", (24 * 60 * 60), function() use ($amount) {
            return parent::fetch(self::baseUrl()."/charges/withdrawal/$amount");
        });
    }
}
