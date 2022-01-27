<?php

namespace App\Interfaces;

use App\Models\Payment;
use App\Models\Transaction;

interface ProductRepositoryInterface
{
    public function createTransaction(array $requestData): Transaction;
    public function createPayment(Transaction $transaction): Payment;
    public function initiatePayment();
    public function requestPurchase();
    public function finalizeTransaction();
    public function notify(): Payment;
}
