<?php

namespace App\Listeners;

use App\Enums\Status;
use App\Events\TransactionSuccessEvent;
use App\Models\Transaction;
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
     * @param TransactionSuccessEvent $event
     * @return void
     */
    public function handle(TransactionSuccessEvent $event)
    {
        Log::info('--- --- --- --- ---   ...[EVENT]: Transaction Success...   --- --- --- --- ---');

//        TODO: Fix earnings logic ASAP!
        EarningRepository::calculateEarnings($event->transaction, $event->totalEarned);

        Transaction::updateStatus($event->transaction, Status::COMPLETED);
    }
}
