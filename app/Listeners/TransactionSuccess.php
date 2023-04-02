<?php

namespace App\Listeners;

use App\Enums\Status;
use App\Events\TransactionSuccessEvent;
use App\Repositories\EarningRepository;
use Illuminate\Support\Facades\Log;

class TransactionSuccess
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
     *
     * @throws \Exception
     * @throws \Throwable
     */
    public function handle(TransactionSuccessEvent $event): void
    {
        Log::info('...[EVENT]: Transaction Success...');

        EarningRepository::calculateEarnings($event->transaction, $event->totalEarned);

        $event->transaction->update(['status' => Status::COMPLETED]);
    }
}
