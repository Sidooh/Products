<?php


namespace App\Helpers\AfricasTalking;


use AfricasTalking\SDK\AfricasTalking;

class AfricasTalkingSubClass extends AfricasTalking
{
    public function transaction()
    {
        $transaction = new Transaction($this->tokenClient, $this->username, $this->apiKey);
        return $transaction;
    }
}
