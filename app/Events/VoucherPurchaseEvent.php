<?php


namespace App\Events;


use App\Models\Transaction;
use App\Models\Voucher;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoucherPurchaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Voucher
     */
    public Voucher $voucher;

    /**
     * @var Transaction
     */
    public Transaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param Voucher     $voucher
     * @param Transaction $transaction
     */
    public function __construct(Voucher $voucher, Transaction $transaction)
    {
        $this->voucher = $voucher;
        $this->transaction = $transaction;
    }
}
