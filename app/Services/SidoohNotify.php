<?php

namespace App\Services;

use App\Enums\EventType;
use App\Models\Notification;
use Error;
use Exception;
use Illuminate\Support\Facades\Log;

class SidoohNotify extends SidoohService
{
    public static function notify(array $to, string $message, EventType $eventType): void
    {
        Log::info('...[SRV - NOTIFY]: Send Notification...');

        $url = config('services.sidooh.services.notify.url') . "/notifications";

        try {
            $response = parent::fetch($url, "POST", [
                "channel" => "SMS",
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
        } catch (Exception|Error $e) {
            Notification::create([
                'to' => $to,
                'message' => $message,
                'event' => $eventType,
                'response' => ["err" => $e->getMessage()]
            ]);

        }
    }
}
