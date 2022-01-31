<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Helpers\Product\Purchase;
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
    public array $paymentData, $account;
    private string $product;

    /**
     * @param PaymentRepository $paymentRepo
     */
    #[Pure]
    public function __construct(private PaymentRepository $paymentRepo = new PaymentRepository())
    {
    }

    /**
     * @throws Exception
     */
    public function createTransaction(array $transactionData): ProductRepository
    {
        $this->product = $transactionData['product'];
        $this->transaction = Transaction::create($transactionData);
        $this->paymentRepo->setAmount($this->transaction->amount)->setProduct($transactionData['product']);
        $this->paymentRepo->setAccountId($this->transaction->account_id)->setProduct($transactionData['product']);

        return $this;
    }

    public function createPayment(array $paymentData = null): ProductRepository
    {
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
    public function initiatePayment($destination = null, $mpesaNumber = null): static
    {
        Log::info("====== Product Purchase ({$this->paymentMethod->value}) ======");
        if($this->product === 'airtime' || 'voucher') {
            $destination = $destination
                ? ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+')
                : $this->account['phone'];
            $mpesaNumber = $mpesaNumber
                ? ltrim(PhoneNumber::make($mpesaNumber, 'KE')->formatE164(), '+')
                : $this->account['phone'];
        }
        Log::info("$destination - $mpesaNumber");

        $this->paymentData = match ($this->paymentMethod) {
            PaymentMethod::MPESA => $this->paymentRepo->mpesa($destination, $mpesaNumber),
            PaymentMethod::VOUCHER => $this->paymentRepo->voucher($this->account, $destination),
            default => throw new Exception('Unexpected match value')
        };

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function requestPurchase($purchaseData = [])
    {
        if(empty($purchaseData)) $purchaseData = $this->paymentData;

        $purchase = new Purchase;

        match ($this->product) {
            'airtime' => $purchase->airtime($this->transaction, $purchaseData),
            'utility' => $purchase->utility($this->transaction, $purchaseData, $purchaseData['provider']),
            'subscription' => $purchase->subscription($this->transaction, $purchaseData['amount']),
            'voucher' => $purchase->voucher($this->transaction)
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
     * @param array $account
     */
    public function setAccount(array $account): void
    {
        $this->account = $account;
    }

    /**
     * @param PaymentMethod $paymentMethod
     */
    public function setPaymentMethod(PaymentMethod $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }
}
