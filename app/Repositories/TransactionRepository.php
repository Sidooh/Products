<?php

namespace App\Repositories;

use App\Events\TransactionCreated;
use App\Helpers\Product\Purchase;
use App\Models\Transaction;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Throwable;

class TransactionRepository
{
    use ApiResponse;

    public array $data;

    public static function createTransaction(array $transactions, $data): array
    {
        $transactions = array_map(fn($transaction) => [
            ...Transaction::create($transaction)->toArray(),
            "account" => $transaction['account']
        ], $transactions);

        TransactionCreated::dispatch($transactions, $data);

        return Arr::pluck($transactions, 'id');
    }

    /**
     * @throws RequestException
     */
    public static function initiatePayment(array $transactions, array $data)
    {
        $totalAmount = collect($transactions)->sum("amount");

        SidoohPayments::pay($transactions, $data['method'], $totalAmount, $data);
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
            'subscription' => $purchase->subscription(),
            default => throw new Exception("Invalid product purchase!"),
        };
    }
}
