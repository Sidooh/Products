<?php

namespace App\Helpers\Kyanda;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Exceptions\KyandaException;
use Nabcellent\Kyanda\Facades\Utility;
use Nabcellent\Kyanda\Models\KyandaRequest;

class KyandaApi
{
    public static function airtime(Transaction $transaction, int $phone): bool|KyandaRequest|array
    {
        Log::info('--- --- --- --- ---   ...[KYANDA-API]: Disburse Airtime...   --- --- --- --- ---');

        try {
            return Utility::airtimePurchase($phone, $transaction->amount, $transaction->id);
        } catch (KyandaException $e) {
            Log::error('KyandaError: '.$e->getMessage());
        }

        return true;
    }

    public static function bill(Transaction $transaction, string $provider): bool|KyandaRequest|array
    {
        Log::info('--- --- --- --- ---   ...[KYANDA-API]: Disburse Utility...   --- --- --- --- ---');

        try {
            return Utility::billPayment($transaction->destination, $transaction->amount, $provider, 700000000, $transaction->id);
        } catch (KyandaException $e) {
            Log::error('KyandaError: '.$e->getMessage());
        }

        return true;
    }
}
