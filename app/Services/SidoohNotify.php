<?php

namespace App\Services;

use App\Enums\EventType;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SidoohNotify extends SidoohService
{
    public static function notify(array $to, string $message, EventType $eventType)
    {
        Log::info('--- --- --- --- ---   ...[SRV - NOTIFY]: Send Notification...   --- --- --- --- ---', [
            "channel" => "sms",
            "event_type" => $eventType->value,
            "destination" => implode(', ', $to),
            "content" => $message
        ]);

        $url = config('services.sidooh.services.notify.url') . "/notifications";

        try {

            $response = parent::http()->post($url, [
                "channel" => "sms",
                "event_type" => $eventType->value,
                "destination" => $to,
                "content" => $message
            ])->json();

            Log::info('--- --- --- --- ---   ...[SRV - NOTIFY]: Notification Sent...   --- --- --- --- ---', [
                'ids' => $response["ids"]
            ]);

        } catch (ConnectionException $e) {

            Log::error("Failed to Connect to Notify!");

        } catch (Exception $e) {

            Log::error($e);

        }
    }
}
