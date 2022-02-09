<?php

namespace App\Listeners;

use App\Helpers\SidoohNotify\EventTypes;
use App\Repositories\EventRepositories\KyandaEventRepository;
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

        KyandaEventRepository::transactionFailed($event->transaction);
    }
}
