<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SidoohSavings extends SidoohService
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function withdrawEarnings(Collection $transactions, PaymentMethod $method): array
    {
        Log::info('...[SRV - SAVINGS]: Withdraw Earnings...');

        $url = config('services.sidooh.services.savings.url').'/accounts/earnings/withdraw';

        $data = $transactions->map(function($t) use ($method) {
            return [
                'ref'         => "$t->id",
                'account_id'  => $t->account_id,
                'amount'      => $t->amount,
                'method'      => $method->name,
                'destination' => $t->destination,
            ];
        });

        return parent::fetch($url, 'POST', $data->toArray());
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
