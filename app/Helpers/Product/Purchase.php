<?php

namespace App\Helpers\Product;

use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Models\Transaction;
use Exception;
use Throwable;
use function config;

class Purchase
{
    /**
     * @throws Exception
     */
    public function utility(Transaction $transaction, array $billDetails, string $provider): void
    {
        match (config('services.sidooh.utilities_provider')) {
            'KYANDA' => KyandaApi::bill($transaction, $billDetails, $provider),
            'TANDA' => TandaApi::bill($transaction, $billDetails, $provider),
            default => throw new Exception('No provider provided for utility purchase')
        };
    }

    /**
     * @param Transaction $transaction
     * @param array       $airtimeData
     * @throws Throwable
     */
    public function airtime(Transaction $transaction, array $airtimeData): void
    {
        if($transaction->airtime) exit;

        match (config('services.sidooh.utilities_provider')) {
            'AT' => AfricasTalkingApi::airtime($transaction, $airtimeData),
            'KYANDA' => KyandaApi::airtime($transaction, $airtimeData),
            'TANDA' => TandaApi::airtime($transaction, $airtimeData),
            default => throw new Exception('No provider provided for airtime purchase')
        };
    }
}
