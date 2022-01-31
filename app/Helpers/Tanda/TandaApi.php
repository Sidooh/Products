<?php

namespace App\Helpers\Tanda;

use App\Models\Transaction;
use DrH\Tanda\Exceptions\TandaException;
use DrH\Tanda\Facades\Utility;
use Illuminate\Support\Facades\Log;

class TandaApi
{
    public static function airtime(Transaction $transaction, array $array): void
    {
        try {
            Utility::airtimePurchase($array['phone'], $array['amount'], $transaction->id);
        } catch (TandaException $e) {
            Log::error("TandaError: " . $e->getMessage(), [$transaction]);
        }
    }

    public static function bill(Transaction $transaction, array $array, string $provider): void
    {
        try {
            Utility::billPayment($array['account_number'], $array['amount'], $provider, $transaction->id);
        } catch (TandaException $e) {
            Log::error("TandaError: " . $e->getMessage(), [$transaction]);
        }
    }
}
