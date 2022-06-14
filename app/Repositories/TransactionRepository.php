<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Enums\ProductType;
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

        if(isset($response["data"]) && $data['method'] === PaymentMethod::VOUCHER->value) {
            self::requestPurchase($response["data"]);
        }

        return response()->json(["status" => "success", "message" => "Purchase Completed!"]);
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(array $paymentsData): void
    {
        $transactions = Transaction::findMany(Arr::pluck($paymentsData["payments"], "payable_id"));

        try {
            foreach($transactions as $transaction) {
                $purchase = new Purchase($transaction);

                match ($transaction->product_id) {
                    ProductType::AIRTIME => $purchase->airtime(),
                    ProductType::UTILITY => $purchase->utility($paymentsData),
                    ProductType::SUBSCRIPTION => $purchase->subscription(),
                    default => throw new Exception("Invalid product purchase!"),
                };
            }
        } catch (Exception $err) {
            Log::error($err);
        }
    }
}
