<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SidoohSavings extends SidoohService
{
    public static function withdrawEarnings(Transaction $transaction, PaymentMethod $method): array
    {
        Log::info('...[SRV - SAVINGS]: Withdraw Earnings...');

        $url = config('services.sidooh.services.savings.url').'/accounts/earnings/withdraw';

        $data = [
                'ref'         => "$transaction->id",
                'account_id'  => $transaction->account_id,
                'amount'      => $transaction->amount,
                'method'      => $method->name,
                'destination' => $transaction->destination,
            ];

        return parent::fetch($url, 'POST', $data);
    }

    public static function save(array $savings)
    {
        Log::info('...[SRV - SAVINGS]: Save...', ['savings' => $savings]);

        $url = config('services.sidooh.services.savings.url').'/accounts/earnings';

        try {
            $response = parent::http()->post($url, $savings)->json();

            Log::info('...[SRV - SAVINGS]: Savings data Sent...', $response);

            return $response;
        } catch (ConnectionException $e) {
            Log::error('Failed to Connect to Savings!', ['err' => $e->getMessage()]);
        } catch (Exception $e) {
            Log::error($e);
        }
        throw new \Error('Failed to save earnings');
    }
}
