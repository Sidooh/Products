<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\TandaEventRepository;
use DrH\Tanda\Events\TandaRequestSuccessEvent;
use Illuminate\Support\Facades\Log;

class TandaRequestSuccess
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
     * @param TandaRequestSuccessEvent $event
     * @return void
     */
    public function handle(TandaRequestSuccessEvent $event)
    {
        Log::info('...[EVENT]: Tanda Request Success...', [
            "id" => $event->request->id
        ]);

        TandaEventRepository::requestSuccess($event->request);
    }
}
