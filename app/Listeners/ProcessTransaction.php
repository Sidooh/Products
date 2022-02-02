<?php

namespace App\Listeners;

use App\Events\TransactionCreated;
use App\Repositories\ProductRepository;
use Exception;
use Throwable;

class ProcessTransaction
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
     * @param TransactionCreated $event
     * @return void
     * @throws Exception|Throwable
     */
    public function handle(TransactionCreated $event)
    {
        $repo = new ProductRepository($event->transaction);
        $repo->init($event->data);
    }
}
