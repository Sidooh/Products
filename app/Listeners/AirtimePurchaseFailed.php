<?php

namespace App\Listeners;

use App\Events\AirtimePurchaseFailedEvent;
use App\Repositories\EventRepositories\ATEventRepository;
use Illuminate\Support\Facades\Log;

class AirtimePurchaseFailed
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Handle the event.
     *
     * @param  AirtimePurchaseFailedEvent  $event
     * @return void
     */
    public function handle(AirtimePurchaseFailedEvent $event)
    {
        Log::info('--- --- --- --- ---   ...[EVENT]: Airtime Purchase Failed...   --- --- --- --- ---', [
            'response' => $event->airtime_response,
        ]);

        ATEventRepository::airtimePurchaseFailed($event->airtime_response);
    }
}
