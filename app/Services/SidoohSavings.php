<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class SidoohSavings extends SidoohService
{
    public static function withdrawEarnings(Transaction $transaction, PaymentMethod $method): array
    {
        Log::info('...[SRV - SAVINGS]: Withdraw Earnings...');

        $url = config('services.sidooh.services.savings.url') . '/accounts/' . $transaction->account_id . '/earnings/withdraw';

        $data = [
            'amount'              => $transaction->amount,
            'reference'           => "$transaction->id",
            'destination'         => $method->name,
            'destination_account' => $transaction->destination,
            'ipn'                 => config('app.url').'/api/sidooh/savings/callback',
        ];

        return parent::fetch($url, 'POST', $data);
    }

    public static function save(array $savings)
    {
        Log::info('...[SRV - SAVINGS]: Save...');

        $url = config('services.sidooh.services.savings.url') . '/accounts/earnings';

        return parent::fetch($url, 'POST', $savings);
    }
}
