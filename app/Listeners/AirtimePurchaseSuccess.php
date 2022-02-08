<?php

namespace App\Listeners;

use App\Events\AirtimePurchaseSuccessEvent;
use App\Helpers\SidoohNotify\EventTypes;
use App\Repositories\NotificationRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Support\Facades\Log;

class AirtimePurchaseSuccess
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
     * @param AirtimePurchaseSuccessEvent $event
     * @return void
     */
    public function handle(AirtimePurchaseSuccessEvent $event)
    {
        //
//        TODO:: Send sms notification

        Log::info('----------------- Airtime Purchase Success ');



    }

//    TODO: Refactor this to external file?
    public function getPointsEarned(float $discount)
    {
        $e = $discount * config('services.sidooh.earnings.users_percentage');
        return 'KES' . $e / 6;
    }
}
