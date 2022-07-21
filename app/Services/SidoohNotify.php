<?php

namespace App\Services;

use App\Enums\EventType;
use Illuminate\Support\Facades\Log;

class SidoohNotify extends SidoohService
{
    public static function notify(array $to, string $message, EventType $eventType)
    {
        Log::info('...[SRV - NOTIFY]: Send Notification...');

        $url = config('services.sidooh.services.notify.url') . "/notifications";

        return parent::fetch($url, "POST", [
            "channel" => "sms",
            "event_type" => $eventType->value,
            "destination" => $to,
            "content" => $message
        ]);

//        try {
//
//            $response = parent::http()->post($url, [
//                "channel" => "sms",
//                "event_type" => $eventType->value,
//                "destination" => $to,
//                "content" => $message
//            ])->json();
//
//            Log::info('...[SRV - NOTIFY]: Notification Sent...', [
//                'ids' => $response["ids"]
//            ]);
//
//        } catch (ConnectionException $e) {
//
//            Log::error("Failed to Connect to Notify!", ["err" => $e->getMessage()]);
//
//        } catch (Exception $e) {
//
//            Log::error($e);
//
//        }
    }
}
