<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Helpers\Product\Purchase;
use App\Models\Payment;
use App\Models\Transaction;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\Pure;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class ProductRepository
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
     * @throws Exception|Throwable
     */
    public function init($data)
    {
        $this->data = $data;

        $this->paymentRepo->setData($this->data);
        $this->paymentRepo->setTransaction($this->transaction);

        $targetNumber = $data['target_number'] ?? null;
        $mpesaNumber = $data['mpesa_number'] ?? null;

        $this->initiatePayment($targetNumber, $mpesaNumber);
    }

    /**
     * @throws Exception|Throwable
     */
    public function initiatePayment($destination = null, $mpesaNumber = null)
    {
        $paymentMethod = PaymentMethod::tryFrom($this->data['method']);

        Log::info("====== Product Purchase (Method:{$paymentMethod->value}) ======");
        if($this->data['product'] === 'airtime' || 'voucher') {
            $destination = $destination
                ? ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
            $mpesaNumber = $mpesaNumber
                ? ltrim(PhoneNumber::make($mpesaNumber, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
        }
        Log::info("$destination - $mpesaNumber");

        match ($paymentMethod) {
            PaymentMethod::MPESA => $this->paymentRepo->mpesa($destination, $mpesaNumber),
            PaymentMethod::VOUCHER => $this->paymentRepo->voucher($this->data['account'], $destination),
            default => throw new Exception('Unexpected match value')
        };
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Transaction $transaction, array $purchaseData)
    {
        $purchase = new Purchase;

        match ($purchaseData['product']) {
            'airtime' => $purchase->airtime($transaction, $purchaseData),
            'utility' => $purchase->utility($transaction, $purchaseData, $purchaseData['provider']),
            'subscription' => $purchase->subscription($transaction, $purchaseData['amount']),
            'voucher' => $purchase->voucher($transaction)
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
