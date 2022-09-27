<?php

namespace App\Listeners;

use App\Events\VoucherPurchaseEvent;
use App\Repositories\EventRepositories\SidoohEventRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class VoucherPurchaseSuccess
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
     * @param VoucherPurchaseEvent $event
     * @return void
     *
     * @throws Exception
     */
    public function handle(VoucherPurchaseEvent $event): void
    {
        Log::info('...[EVENT]: Voucher Purchase Success...');

        SidoohEventRepository::voucherPurchaseSuccess($event->transaction, $event->vouchers);
    }
}
