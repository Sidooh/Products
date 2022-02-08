<?php

namespace App\Listeners;

use App\Events\AirtimePurchaseFailedEvent;
use App\Helpers\SidoohNotify\EventTypes;
use App\Repositories\EventRepositories\ATEventRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Log;

class AirtimePurchaseFailed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() { }

    /**
     * Handle the event.
     *
     * @param AirtimePurchaseFailedEvent $event
     * @return void
     */
    public function handle(AirtimePurchaseFailedEvent $event)
    {
        Log::info('----------------- Airtime Purchase Failed');
        Log::info($event->airtime_response);

        ATEventRepository::airtimePurchaseFailed($event->airtime_response);
    }
}
