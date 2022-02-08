<?php


namespace App\Events;


use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MerchantPurchaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Merchant
     */
    public Merchant $merchant;

    /**
     * @var Transaction
     */
    public Transaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param Merchant $merchant
     * @param Transaction $transaction
     */
    public function __construct(Merchant $merchant, Transaction $transaction)
    {
        $this->merchant = $merchant;
        $this->transaction = $transaction;
    }
}
