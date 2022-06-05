<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Enums\Status;
use App\Events\TransactionCreated;
use App\Helpers\Product\Purchase;
use App\Models\Transaction;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
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

        TransactionCreated::dispatch($transactions, $data);

        return Arr::pluck($transactions, 'id');
    }

    /**
     * @throws AuthenticationException
     * @throws \Throwable
     */
    public static function initiatePayment(array $transactions, array $data): JsonResponse
    {
        $totalAmount = collect($transactions)->sum("amount");

        $response = SidoohPayments::pay($transactions, $data['method'], $totalAmount, $data);

        if($data['method'] === PaymentMethod::VOUCHER->value && $response) {
            self::requestPurchase(Arr::pluck($transactions, "id"), $data);
        }

        return response()->json(["status" => "success", "message" => "Purchase Completed!"]);
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(array $transactionIds, array $purchaseData): void
    {
        Transaction::whereIn("id", $transactionIds)->update(['status' => Status::COMPLETED]);
        $transactions = Transaction::findMany($transactionIds);

        try {
            foreach($transactions as $transaction) {
                $purchase = new Purchase($transaction);

                match ($purchaseData['product']) {
                    'airtime' => $purchase->airtime($purchaseData),
                    'utility' => $purchase->utility($purchaseData, $purchaseData['provider']),
                    'subscription' => $purchase->subscription(),
                    default => throw new Exception("Invalid product purchase!"),
                };
            }
        } catch (Exception $err) {
            Log::error($err);
        }
    }
}
