<?php

namespace App\Interfaces;

use App\Models\Payment;
use App\Repositories\ProductRepository;

interface ProductRepositoryInterface
{
    public function createTransaction(array $transactionData): ProductRepository;
    public function createPayment(array $paymentData): ProductRepository;
    public function initiatePayment($initiatorPhone, $targetNumber = null, $mpesaNumber = null);
    public function requestPurchase(string $product, array $productData);
    public function finalizeTransaction();
    public function notify(): Payment;
}
