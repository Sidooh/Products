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

    private Payment|Model $payment;
    public array $data;
    private PaymentRepository $paymentRepo;

    /**
     * @param Transaction $transaction
     */
    #[Pure]
    public function __construct(public Transaction $transaction)
    {
        $this->paymentRepo = new PaymentRepository();
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function init($data)
    {
//        dump_json($data);
        $this->data = $data;

        $this->paymentRepo->setData($this->data);

        $targetNumber = $data['target_number'] ?? null;
        $mpesaNumber = $data['mpesa_number'] ?? null;

        $this->data += $this->initiatePayment($targetNumber, $mpesaNumber);
        $this->createPayment()->requestPurchase();
    }

    public function createPayment(): ProductRepository
    {
        if(isset($this->transaction)) {
            $this->payment = $this->transaction->payment()->create($this->data);
        } else if(isset($paymentData) || isset($this->paymentData)) {
            $this->payment = Payment::create($paymentData ?? $this->data);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function initiatePayment($destination = null, $mpesaNumber = null): ?array
    {
        $paymentMethod = PaymentMethod::tryFrom($this->data['method']);

        Log::info("====== Product Purchase ({$paymentMethod->value}) ======");
        if($this->data['product'] === 'airtime' || 'voucher') {
            $destination = $destination
                ? ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
            $mpesaNumber = $mpesaNumber
                ? ltrim(PhoneNumber::make($mpesaNumber, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
        }
        Log::info("$destination - $mpesaNumber");

        return match ($paymentMethod) {
            PaymentMethod::MPESA => $this->paymentRepo->mpesa($destination, $mpesaNumber),
            PaymentMethod::VOUCHER => $this->paymentRepo->voucher($this->data['account'], $destination),
            default => throw new Exception('Unexpected match value')
        };
    }

    /**
     * @throws Throwable
     */
    public function requestPurchase()
    {
        $purchase = new Purchase;

        match ($this->data['product']) {
            'airtime' => $purchase->airtime($this->transaction, $this->data),
            'utility' => $purchase->utility($this->transaction, $this->data, $this->data['provider']),
            'subscription' => $purchase->subscription($this->transaction, $this->data['amount']),
            'voucher' => $purchase->voucher($this->transaction)
        };
    }

    /**
     * @return Transaction
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
