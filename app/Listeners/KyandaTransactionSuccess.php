<?php

namespace App\Listeners;

use App\Helpers\SidoohNotify\EventTypes;
use App\Repositories\EventRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Events\KyandaTransactionSuccessEvent;

class KyandaTransactionSuccess
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
     * @throws Exception
     */
    public function handle(KyandaTransactionSuccessEvent $event)
    {
        //
        Log::info('----------------- Kyanda Transaction Success ');
        Log::info($event->transaction->request->provider);

        EventRepository::kyandaTransactionSuccess($event->transaction);
    }
}
