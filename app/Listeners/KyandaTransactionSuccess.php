<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\KyandaEventRepository;
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
    public function __construct() { }

    /**
     * Handle the event.
     *
     * @param KyandaTransactionSuccessEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(KyandaTransactionSuccessEvent $event)
    {
        Log::info('--- --- --- --- ---   ...[EVENT]: Kyanda Transaction Success...   --- --- --- --- ---', [
            "" => $event->transaction->request->provider
        ]);

        KyandaEventRepository::transactionSuccess($event->transaction);
    }
}
