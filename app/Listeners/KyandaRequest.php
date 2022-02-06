<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\EventRepository;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Events\KyandaRequestEvent;
use Nabcellent\Kyanda\Events\KyandaTransactionSuccessEvent;

class KyandaRequest
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param KyandaTransactionSuccessEvent $event
     * @return void
     */
    public function handle(KyandaRequestEvent $event)
    {
        //
        Log::info('----------------- Kyanda Request: ' . $event->request->status . ' - ' . $event->request->message);

        EventRepository::kyandaRequest($event->request);
    }
}
