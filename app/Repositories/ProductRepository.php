<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Helpers\Product\Purchase;
use App\Helpers\Sidooh\USSD\Entities\PaymentMethods;
use App\Interfaces\ProductRepositoryInterface;
use App\Models\Payment;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\Pure;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class ProductRepository implements ProductRepositoryInterface
{
    use ApiResponse;

    private Transaction $transaction;
    private Payment|Model $payment;
    private PaymentMethod $paymentMethod;
    private array $paymentData;

    /**
     * @param PaymentRepository $paymentRepo
     */
    #[Pure]
    public function __construct(private PaymentRepository $paymentRepo = new PaymentRepository())
    {
    }

    public function createTransaction(array $transactionData): ProductRepository
    {
        // TODO: Implement createTransaction() method.
        $this->transaction = Transaction::create($transactionData);
        $this->paymentRepo->setAmount($this->transaction->amount);

        return $this;
    }

    public function createPayment(array $paymentData = null): ProductRepository
    {
        // TODO: Implement createPayment() method.
        if(isset($this->transaction)) {
            $this->payment = $this->transaction->payment()->create($this->paymentData);
        } else if(isset($paymentData) || isset($this->paymentData)) {
            $this->payment = Payment::create($paymentData ?? $this->paymentData);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function initiatePayment($initiatorPhone, $targetNumber = null, $mpesaNumber = null): static
    {
        Log::info("====== Airtime Purchase ({$this->paymentMethod->value}) ======");
        $targetNumber = $targetNumber
            ? ltrim(PhoneNumber::make($targetNumber, 'KE')->formatE164(), '+')
            : $initiatorPhone;
        $mpesaNumber = $mpesaNumber
            ? ltrim(PhoneNumber::make($mpesaNumber, 'KE')->formatE164(), '+')
            : '';
        Log::info("$targetNumber - $mpesaNumber");

        $this->paymentRepo->setPhone($initiatorPhone);

        $this->paymentData = match ($this->paymentMethod) {
            PaymentMethod::MPESA => $this->paymentRepo->mpesa($targetNumber, $mpesaNumber),
            PaymentMethod::VOUCHER => $this->paymentRepo->voucher($targetNumber),
            default => throw new Exception('Unexpected match value')
        };

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function requestPurchase($product, $productData = [])
    {
        if(empty($productData)) $productData = $this->paymentData;

        match($product) {
            'airtime' => (new Purchase)->airtime($this->transaction, $productData),
            'utility' => (new Purchase)->utility($this->transaction, $productData, '')
        };
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

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }
}
