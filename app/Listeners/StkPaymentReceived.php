<?php

namespace App\Listeners;

use App\Repositories\EventRepository;
use DrH\Mpesa\Events\StkPushPaymentSuccessEvent;
use Illuminate\Support\Facades\Log;

class StkPaymentReceived
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
     * @param StkPushPaymentSuccessEvent $event
     * @return void
     */
    public function handle(StkPushPaymentSuccessEvent $event)
    {
        Log::info('----------------- STK Payment Received ');

        EventRepository::stkPaymentReceived($event->stkCallback);
    }
}
