<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\TandaEventRepository;
use DrH\Tanda\Events\TandaRequestFailedEvent;
use Exception;
use Illuminate\Support\Facades\Log;

class TandaRequestFailed
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
     * @param TandaRequestFailedEvent $event
     * @return void
     * @throws Exception
     */
    public function handle(TandaRequestFailedEvent $event)
    {
        Log::info('--- --- --- --- ---   ...[EVENT]: Tanda Request Failed...   --- --- --- --- ---', [
            'id'      => $event->request->id,
            'message' => $event->request->message
        ]);

        TandaEventRepository::requestFailed($event->request);
    }
}
