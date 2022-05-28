<?php

namespace App\Events;

use App\Models\AirtimeResponse;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AirtimePurchaseSuccessEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param AirtimeResponse $response
     */
    public function __construct(public AirtimeResponse $airtime_response) { }
}
