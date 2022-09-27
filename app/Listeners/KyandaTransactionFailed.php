<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\KyandaEventRepository;
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
    }

    /**
     * Handle the event.
     *
     * @param KyandaTransactionFailedEvent $event
     * @return void
     *
     * @throws Exception
     */
    public function handle(KyandaTransactionFailedEvent $event)
    {
        Log::info('...[EVENT]: Kyanda Transaction Failed...', [
            'transaction' => $event->transaction,
        ]);

        KyandaEventRepository::transactionFailed($event->transaction);
    }
}
