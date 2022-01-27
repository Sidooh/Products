<?php

namespace App\Interfaces;

use App\Models\Payment;
use App\Models\Transaction;
use App\Repositories\ProductRepository;

interface ProductRepositoryInterface
{
    public function createTransaction(array $transactionData): ProductRepository;
    public function createPayment(array $paymentData): ProductRepository;
    public function initiatePayment(Transaction $transaction, string $method);
    public function requestPurchase();
    public function finalizeTransaction();
    public function notify(): Payment;
}
