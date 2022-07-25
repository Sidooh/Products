<?php

namespace App\Helpers\Tanda;

use App\Enums\EventType;
use App\Models\Transaction;
use App\Services\SidoohNotify;
use DrH\Tanda\Exceptions\TandaException;
use DrH\Tanda\Facades\Utility;
use DrH\Tanda\Library\EventHelper;
use DrH\Tanda\Models\TandaRequest;
use Exception;
use Illuminate\Support\Facades\Log;

class TandaApi
{
    public static function airtime(Transaction $transaction, int $phone): void
    {
        Log::info('...[TANDA-API]: Disburse Airtime...');

        // TODO: Remove in Production
        $transaction->amount = 10;

        try {
            $response = Utility::airtimePurchase($phone, $transaction->amount, $transaction->id);
            self::handleRequestResponse($response);
        } catch (TandaException $e) {
            Log::error("TandaError: " . $e->getMessage(), [$transaction]);
        }
    }

    public static function bill(Transaction $transaction, string $provider): void
    {
        Log::info('...[TANDA-API]: Disburse Utility...');

        try {
            $response = Utility::billPayment($transaction->destination, $transaction->amount, $provider, $transaction->id);
            self::handleRequestResponse($response);
        } catch (TandaException $e) {
            Log::error("TandaError: " . $e->getMessage(), [$transaction]);
        }
    }

    private static function handleRequestResponse(TandaRequest $request): void
    {
        if ($request->status == 2) {
            try {
                $message = "TN_ERROR-{$request->relation->id}\n";
                $message .= "{$request->provider} - {$request->destination}\n";
                $message .= "{$request->message}\n";
                $message .= "{$request->created_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"))}";

                SidoohNotify::notify(
                    ['254714611696', '254711414987', '254721309253'], $message, EventType::ERROR_ALERT
                );

                Log::info('...[TANDA-API]: Airtime/Utility Failure SMS Sent...');
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }

    public static function queryStatus(Transaction $transaction, string $requestId): void
    {
        Log::info('...[TANDA-API]: Query Status...');

        $response = Utility::requestStatus($requestId);

        if (is_null($transaction->tandaRequest)) {
            // TODO: Do some validation checks here to ensure this and transaction match to some degree
            $request = TandaRequest::create([
                'request_id' => $response['id'],
                'status' => $response['status'],
                'message' => $response['message'],
                'receipt_number' => $response['receiptNumber'],
                'command_id' => $response['commandId'],
                'provider' => $response['serviceProviderId'],
                'destination' => $transaction->destination,
                'amount' => $transaction->amount,
                'result' => $response['resultParameters'],
                'last_modified' => $response['datetimeLastModified'],
                'relation_id' => $transaction->id
            ]);

            EventHelper::fireTandaEvent($request);
        }
    }
}
