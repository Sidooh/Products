<?php

namespace App\Events;

use App\Models\ATAirtimeResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AirtimePurchaseSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  ATAirtimeResponse  $response
     */
    public function __construct(public ATAirtimeResponse $airtime_response)
    {
    }
}
