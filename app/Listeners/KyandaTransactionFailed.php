<?php

namespace App\Listeners;

use App\Helpers\SidoohNotify\EventTypes;
use App\Repositories\EventRepositories\EventRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Events\KyandaTransactionFailedEvent;

class KyandaTransactionFailed
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
     * @param KyandaTransactionFailedEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(KyandaTransactionFailedEvent $event)
    {
        //
        Log::info('----------------- Kyanda Transaction Failed ');
        Log::error($event->transaction);

        EventRepository::kyandaTransactionFailed($event->transaction);
    }
}
