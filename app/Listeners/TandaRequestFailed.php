<?php

namespace App\Listeners;

use App\Repositories\EventRepositories\TandaEventRepository;
use DrH\Tanda\Events\TandaRequestFailedEvent;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class TandaRequestFailed
{
    /**
     * @throws RequestException
     * @throws AuthenticationException
     */
    public function handle(TandaRequestFailedEvent $event): void
    {
        Log::info('...[EVENT]: Tanda Request Failed...', [
            'id'      => $event->request->id,
            'message' => $event->request->message,
        ]);

        TandaEventRepository::requestFailed($event->request);
    }
}
