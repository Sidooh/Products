<?php

namespace App\Helpers\Kyanda;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Exceptions\KyandaException;
use Nabcellent\Kyanda\Facades\Utility;

class KyandaApi
{
    public static function airtime(Transaction $transaction, array $array)
    {
        try {
            return Utility::airtimePurchase($array['phone'], $array['amount'], $transaction->id);
        } catch (KyandaException $e) {
            Log::error("KyandaError: " . $e->getMessage());
        }

        return true;
    }

    public static function bill(Transaction $transaction, array $array, string $provider)
    {
        try {
            return Utility::billPayment($array['account'], $array['amount'], $provider, 700000000, $transaction->id);
        } catch (KyandaException $e) {
            Log::error("KyandaError: " . $e->getMessage());
        }

        return true;
    }
}
