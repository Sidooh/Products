<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Events\TransactionCreated;
use App\Helpers\Product\Purchase;
use App\Models\Transaction;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class TransactionRepository
{
    use ApiResponse;

    public array $data;

    /**
     * @param Transaction $transaction
     */
    public function __construct(public Transaction $transaction) {}

    /**
     * @throws Exception|Throwable
     */
    public function init($data)
    {
        $this->data = $data;

        $targetNumber = $data['target_number'] ?? null;
        $mpesaNumber = $data['mpesa_number'] ?? null;

        $this->initiatePayment($targetNumber, $mpesaNumber);
    }

    public static function createTransaction(array $transactionData, $bulk = false): Transaction|array
    {
        if($bulk) {
            $transactions = array_map(fn($transaction) => [
                ...Transaction::create($transaction)->toArray(),
            ], $transactionData["transactions"]);

            TransactionCreated::dispatch($transactions, $transactionData, $bulk);

            return $transactions;
        }

        $transaction = Transaction::create($transactionData);

        TransactionCreated::dispatch($transaction, $transactionData);

        return $transaction;
    }

    /**
     * @throws RequestException
     */
    public function initiatePayment($destination = null, $mpesaNumber = null)
    {
        Log::info("====== Product Purchase (Method: {$this->data['method']}) ======");
        if(in_array($this->data['product'], ["airtime", "voucher"])) {
            $this->data['destination'] = $destination
                ? ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];
            $this->data['mpesa_number'] = $mpesaNumber
                ? ltrim(PhoneNumber::make($mpesaNumber, 'KE')->formatE164(), '+')
                : $this->data['account']['phone'];

            Log::info("{$this->data['destination']} - {$this->data['mpesa_number']}");
        }

        SidoohPayments::pay($this->transaction->id, PaymentMethod::from($this->data['method']), $this->data['amount'], $this->data);
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
