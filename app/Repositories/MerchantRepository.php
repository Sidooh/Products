<?php

namespace App\Repositories;

use App\Enums\Description;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;

class MerchantRepository
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Throwable
     */
    public function transact(array $request, array $data): Transaction
    {
        $account = SidoohAccounts::find($request['account_id']);
        $charge = SidoohPayments::getPaybillCharge($request['amount']);

        $transactionData = [
            'destination' => $request['business_number'].(isset($request['account_number']) ? ' - '.$request['account_number'] : ''),
            'initiator'   => $request['initiator'],
            'amount'      => $request['amount'],
            'charge'      => $charge,
            'type'        => TransactionType::PAYMENT,
            'description' => Description::MERCHANT_PAYMENT,
            'account_id'  => $request['account_id'],
            'product_id'  => ProductType::MERCHANT,
            'account'     => $account,
        ];

        return TransactionRepository::createTransaction($transactionData, $data);
    }
}
