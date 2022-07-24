<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class SidoohNotify extends SidoohService
{
    public static function notify(array $to, string $message, EventType $eventType): void
    {
        Log::info('...[SRV - NOTIFY]: Send Notification...');

        $url = config('services.sidooh.services.notify.url') . "/notifications";

        try {
            $response = parent::fetch($url, "POST", [
                "channel" => "sms",
                "event_type" => $eventType->value,
                "destination" => $to,
                "content" => $message
            ]);

            Notification::create([
                'to' => $to,
                'message' => $message,
                'event' => $eventType,
                'response' => $response
            ]);

        } catch (ConnectionException $e) {

            Log::error("Failed to Connect to Notify!", ["err" => $e->getMessage()]);

        } catch (Exception $e) {

            Log::error($e);

        }
    }
}
