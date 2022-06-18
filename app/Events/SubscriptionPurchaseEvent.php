<?php


namespace App\Events;


use App\Models\Subscription;
use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPurchaseEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var Subscription
     */
    public Subscription $subscription;

    /**
     * @var Transaction
     */
    public Transaction $transaction;

    /**
     * Create a new event instance.
     *
     * @param Subscription $subscription
     * @param Transaction $transaction
     */
    public function __construct(Subscription $subscription, Transaction $transaction)
    {
        // TODO: Should this be SubscriptionPurchaseSuccessEvent just to be clear?
        $this->subscription = $subscription;
        $this->transaction = $transaction;
    }
}
