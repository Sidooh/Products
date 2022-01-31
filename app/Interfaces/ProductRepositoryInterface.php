<?php

namespace App\Interfaces;

use App\Models\Payment;
use App\Repositories\ProductRepository;

interface ProductRepositoryInterface
{
    public function createTransaction(array $transactionData): ProductRepository;
    public function createPayment(array $paymentData): ProductRepository;
    public function initiatePayment($destination = null, $mpesaNumber = null);
    public function requestPurchase(array $purchaseData);
    public function finalizeTransaction();
    public function notify(): Payment;
}
