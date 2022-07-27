<?php

namespace App\Listeners;

use App\Events\SubscriptionPurchaseSuccessEvent;
use App\Repositories\EarningRepository;
use App\Repositories\EventRepositories\SidoohEventRepository;
use Exception;
use Illuminate\Support\Facades\Log;

class SubscriptionPurchaseSuccess
{

    public bool $afterCommit = true;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct() { }

    /**
     * Handle the event.
     *
     * @param SubscriptionPurchaseSuccessEvent $event
     * @throws Exception
     *@return void
     */
    public function handle(SubscriptionPurchaseSuccessEvent $event): void
    {
        Log::info('...[EVENT]: Subscription Purchase Success...');

        EarningRepository::calculateEarnings($event->transaction, 0);
        SidoohEventRepository::subscriptionPurchaseSuccess($event->subscription, $event->transaction);
    }
}
