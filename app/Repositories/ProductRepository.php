<?php

namespace App\Repositories;

use App\Enums\PaymentSubtype;
use App\Enums\PaymentType;
use App\Enums\Status;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Payment;
use App\Models\Transaction;
use App\Traits\ApiResponse;

class ProductRepository implements ProductRepositoryInterface
{
    use ApiResponse;

    private Transaction $transaction;
    private Payment $payment;

    public function createTransaction(array $transactionData): ProductRepository
    {
        // TODO: Implement createTransaction() method.
        $this->transaction = Transaction::create($transactionData);

        return $this;
    }

    public function createPayment(array $paymentData = []): ProductRepository
    {
        // TODO: Implement createPayment() method.
        if(isset($this->transaction)) {
            $data = $this->transaction->toArray();
            $data['status'] = Status::COMPLETED;
            $data['type'] = PaymentType::SIDOOH;
            $data['subtype'] = PaymentSubtype::VOUCHER;
            $this->payment = $this->transaction->payment()->create($data);
        } else if(count($paymentData)) {
            $this->payment = Payment::create($paymentData);
        }

        return $this;
    }

    public function initiatePayment($transaction, $method)
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



    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * @return Payment
     */
    public function getPayment(): Payment
    {
        return $this->payment;
    }
}
