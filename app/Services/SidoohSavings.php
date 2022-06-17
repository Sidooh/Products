<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SidoohSavings extends SidoohService
{
    public static function save(array $savings)
    {
        Log::info('--- --- ---   ...[SRV - SAVINGS]: Save...   --- --- ---', ["savings" => $savings]);

        $url = config('services.sidooh.services.savings.url') . "/accounts/earnings";

        try {
            $response = parent::http()->post($url, $savings)->json();

            Log::info('--- --- --- --- ---   ...[SRV - SAVINGS]: Notification Sent...   --- --- --- --- ---', $response);
        } catch (ConnectionException $e) {
            Log::error("Failed to Connect to Savings!", ["err" => $e->getMessage()]);
        } catch (Exception $e) {
            Log::error($e);
        }
    }
}
