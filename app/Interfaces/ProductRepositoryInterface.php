<?php

namespace App\Interfaces;

use App\Repositories\ProductRepository;

interface ProductRepositoryInterface
{
    public function createPayment(): ProductRepository;
    public function initiatePayment($destination = null, $mpesaNumber = null);
    public function requestPurchase();
}
