<?php

namespace App\Repositories;

use App\Interfaces\ProductRepositoryInterface;
use App\Models\Payment;
use App\Models\Transaction;

class ProductRepository implements ProductRepositoryInterface
{
    public function createTransaction(array $requestData): Transaction
    {
        // TODO: Implement createTransaction() method.
    }

    public function createPayment(Transaction $transaction): Payment
    {
        // TODO: Implement createPayment() method.
    }

    public function initiatePayment()
    {
        // TODO: Implement initiatePayment() method.
    }

    public function requestPurchase()
    {
        // TODO: Implement requestPurchase() method.
    }

    public function finalizeTransaction()
    {
        // TODO: Implement finalizeTransaction() method.
    }

    public function notify(): Payment
    {
        // TODO: Implement notify() method.
    }
}
