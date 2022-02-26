<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Events\TransactionCreated;
use App\Helpers\Product\Purchase;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\Pure;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class TransactionRepository
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

    public static function createTransaction(array $transactionData): Transaction
    {
        $transaction = Transaction::create($transactionData);

        TransactionCreated::dispatch($transaction, $transactionData);

        return $transaction;
    }

    /**
     * @throws Exception|Throwable
     */
    public function initiatePayment($destination = null, $mpesaNumber = null)
    {
        $paymentMethod = PaymentMethod::tryFrom($this->data['method']);

        Log::info("====== Product Purchase (Method: $paymentMethod->value) ======");
        if($this->data['product'] === 'airtime' || 'voucher') {
            $this->data['destination'] = $destination
                ? ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
            $this->data['mpesa_number'] = $mpesaNumber
                ? ltrim(PhoneNumber::make($mpesaNumber, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
        }
        Log::info("{$this->data['destination']} - {$this->data['mpesa_number']}");

        $payment = SidoohPayments::pay($this->transaction->id, PaymentMethod::from($this->data['method']), $this->data['amount'], $this->data);
        dump_json($payment);

        self::requestPurchase($this->transaction, $this->data);
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Transaction $transaction, array $purchaseData)
    {
        $purchase = new Purchase($transaction);

        match ($purchaseData['product']) {
            'airtime' => $purchase->airtime($purchaseData),
            'utility' => $purchase->utility($purchaseData, $purchaseData['provider']),
            'subscription' => $purchase->subscription($purchaseData['amount']),
            'voucher' => $purchase->voucher(),
            'merchant' => $purchase->merchant($purchaseData['merchant_code']),
            default => throw new Exception("Invalid product purchase!"),
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
