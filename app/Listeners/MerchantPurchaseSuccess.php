<?php

namespace App\Listeners;

use App\Events\MerchantPurchaseEvent;
use App\Helpers\SidoohNotify\EventTypes;
use App\Repositories\EventRepositories\EventRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Log;

class MerchantPurchaseSuccess
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
     * @param MerchantPurchaseEvent $event
     * @return void
     */
    public function handle(MerchantPurchaseEvent $event)
    {
        Log::info('----------------- Merchant Purchase Success');

        EventRepository::merchantPurchaseSuccess($event->transaction, $event->merchant);
    }
}
