<?php

namespace App\Repositories;

use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Helpers\Product\Purchase;
use App\Models\Transaction;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class TransactionRepository
{
    use ApiResponse;

    public array $data;

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    // TODO: Modify to be named create Transactions since it is bulk else create one for bulk specifically
    public static function createTransaction(array $transactionsData, $data): array
    {
//        $transactions = array_map(fn($transaction) => [
//            ...Transaction::create($transaction)->toArray(),
////            "account" => $transaction['account']
//        ], $transactionsData);

        $transactions = collect();
        foreach ($transactionsData as $transactionData) {
            $transactions->add(Transaction::create($transactionData));
        }

        self::initiatePayment($transactions, $data);

        return Arr::pluck($transactions, 'id');
    }

    /**
     * @throws AuthenticationException
     * @throws \Throwable
     */
    public static function initiatePayment(Collection $transactions, array $data): JsonResponse
    {
        $totalAmount = collect($transactions)->sum("amount");

        $response = SidoohPayments::pay($transactions->toArray(), $data['method'], $totalAmount, $data);

        if (isset($response["data"]) && $data['method'] === PaymentMethod::VOUCHER->name) {
            self::requestPurchase($transactions, $response["data"]);
        }

        return response()->json(["status" => "success", "message" => "Purchase Completed!"]);
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Collection $transactions, array $paymentsData): void
    {
        // TODO: Is the response always with successful payments? Can there be failures?
//        $transactions = Transaction::findMany(Arr::pluck($paymentsData["payments"], "payable_id"));

        try {
            foreach ($transactions as $transaction) {
                $purchase = new Purchase($transaction);

                match ($transaction->product_id) {
                    ProductType::AIRTIME => $purchase->airtime(),
                    ProductType::UTILITY => $purchase->utility($paymentsData),
                    ProductType::SUBSCRIPTION => $purchase->subscription(),
                    ProductType::VOUCHER => $purchase->voucher($paymentsData),
                    default => throw new Exception("Invalid product purchase!"),
                };
            }
        } catch (Exception $err) {
            Log::error($err);
        }
    }
}
