<?php

namespace App\Listeners;

use App\Events\AirtimePurchaseSuccessEvent;
use App\Repositories\EventRepositories\ATEventRepository;
use Illuminate\Support\Facades\Log;

class AirtimePurchaseSuccess
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
     * @param AirtimePurchaseSuccessEvent $event
     * @return void
     */
    public function handle(AirtimePurchaseSuccessEvent $event)
    {
        Log::info('...[EVENT]: Airtime Purchase Success...');

        ATEventRepository::airtimePurchaseSuccess($event->airtime_response);
    }
}
