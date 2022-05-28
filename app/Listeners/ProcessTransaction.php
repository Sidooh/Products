<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Repositories\TransactionRepository;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessTransaction implements ShouldQueue
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
     * @param TransactionCreated $event
     * @return void
     * @throws Exception|Throwable
     */
    public function handle(TransactionCreated $event)
    {
        Log::info('--- --- --- --- ---   ...[EVENT]: Process Transaction...   --- --- --- --- ---');

        TransactionRepository::initiatePayment($event->transaction, $event->data);
    }
}
