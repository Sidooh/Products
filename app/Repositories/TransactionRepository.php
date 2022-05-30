<?php

namespace App\Repositories;

use App\Events\TransactionCreated;
use App\Helpers\Product\Purchase;
use App\Models\Transaction;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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

        Log::info("Transaction created:", [$transactions]);

        TransactionCreated::dispatch($transactions, $data);

        Log::info("Transaction dispatch:", [$data]);

        return Arr::pluck($transactions, 'id');
    }

    /**
     * @throws AuthenticationException
     */
    public static function initiatePayment(array $transactions, array $data): void
    {
        $totalAmount = collect($transactions)->sum("amount");

        SidoohPayments::pay($transactions, $data['method'], $totalAmount, $data);
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Transaction $transaction, array $purchaseData): void
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
