<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\EventRepository;
use DrH\Mpesa\Events\StkPushPaymentSuccessEvent;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * @throws Throwable
     */
    public function handle(StkPushPaymentSuccessEvent $event)
    {
        Log::info('----------------- STK Payment Received ');

        EventRepository::stkPaymentReceived($event->stkCallback);
    }
}
