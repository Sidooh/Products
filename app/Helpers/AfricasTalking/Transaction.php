<?php


namespace App\Helpers\AfricasTalking;


use AfricasTalking\SDK\Service;

class Transaction extends Service
{
    public function check($parameters): array
    {
        if (empty($parameters['transactionId'])) {
            return $this->error("transactionId must be specified");
        }

        $response = $this->client->get('query/transaction/find?username=' . $this->username . '&transactionId=' . $parameters['transactionId']);

        return $this->success($response);
    }
}
