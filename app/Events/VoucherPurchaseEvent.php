<?php


namespace App\Events;


use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherPurchaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * Create a new event instance.
     *
     * @param Transaction $transaction
     * @param array $voucher
     * @param array $payment
     */
    public function __construct(public Transaction $transaction, public array $vouchers, public array $payment)
    {
    }
}
