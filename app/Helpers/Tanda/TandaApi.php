<?php

namespace App\Helpers\Tanda;

use App\Enums\EventType;
use App\Models\Transaction;
use App\Services\SidoohNotify;
use DrH\Tanda\Exceptions\TandaException;
use DrH\Tanda\Facades\Utility;
use DrH\Tanda\Models\TandaRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class TandaApi
{
    public static function airtime(Transaction $transaction, array $array): void
    {
        Log::info('--- --- --- --- ---   ...[TANDA-API]: Disburse Airtime...   --- --- --- --- ---');

        // TODO: Remove in Production
        $transaction->amount = 10;

        try {
            $request = Utility::airtimePurchase($array['phone'], $transaction->amount, $transaction->id);
            self::handleRequestResponse($request);
        } catch (TandaException $e) {
            Log::error("TandaError: " . $e->getMessage(), [$transaction]);
        }
    }

    public static function bill(Transaction $transaction, array $array, string $provider): void
    {
        Log::info('--- --- --- --- ---   ...[TANDA-API]: Disburse Utility...   --- --- --- --- ---');

        try {
            Utility::billPayment($array['account_number'], $transaction->amount, $provider, $transaction->id);
        } catch (TandaException $e) {
            Log::error("TandaError: " . $e->getMessage(), [$transaction]);
        }
    }

    private static function handleRequestResponse(TandaRequest $request)
    {
        if($request->status == 2) {
            try {
                $message = "TN_ERROR-{$request->relation->id}\n";
                $message .= "{$request->provider} - {$request->destination}\n";
                $message .= "{$request->message}\n";
                $message .= "{$request->created_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"))}";

                SidoohNotify::notify(
                    ['254714611696', '254711414987', '254721309253'], $message, EventType::ERROR_ALERT
                );

                Log::info('--- --- --- --- ---   ...[TANDA-API]: Airtime/Utility Failure SMS Sent...   --- --- --- --- ---');
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }
}
