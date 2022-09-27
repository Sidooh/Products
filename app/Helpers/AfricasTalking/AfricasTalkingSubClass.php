<?php

namespace App\Helpers\AfricasTalking;

use AfricasTalking\SDK\AfricasTalking;

class AfricasTalkingSubClass extends AfricasTalking
{
    public function transaction(): Transaction
    {
        return new Transaction($this->tokenClient, $this->username, $this->apiKey);
    }
}
