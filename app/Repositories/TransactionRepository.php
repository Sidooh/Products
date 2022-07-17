<?php

namespace App\Repositories;

use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\Product\Purchase;
use App\Models\EarningAccount;
use App\Models\Payment;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use App\Services\SidoohSavings;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
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
    public static function createTransactions(array $transactionsData, $data): array
    {
        $transactions = collect();

        foreach($transactionsData as $transactionData) {
            $transactions->add(Transaction::create($transactionData));
        }

        self::initiatePayment($transactions, $data);

        return Arr::pluck($transactions, 'id');
    }

    /**
     * @throws AuthenticationException
     * @throws \Throwable
     */
    public static function initiatePayment(Collection $transactions, array $data): void
    {
        $totalAmount = collect($transactions)->sum("amount");

        $response = SidoohPayments::pay($transactions->toArray(), $data['method'], $totalAmount, $data);

        if (!isset($response["data"]["payments"])) throw new Exception("Purchase Failed!");

        // TODO: Fix this, payments doesn't know what product expects, product should modify accordingly
        $paymentData = array_map(function ($p) {
            return [
                ...$p,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }, $response["data"]["payments"]);
        Payment::insert($paymentData);

        if (isset($response["data"]) && $data['method'] === PaymentMethod::VOUCHER->name) {
            self::requestPurchase($transactions, $response["data"]);
        }
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Collection $transactions, array $paymentsData): void
    {
        try {
            foreach($transactions as $transaction) {
                Payment::firstWhere("transaction_id", $transaction->id)->update(["status" => Status::COMPLETED]);

                $purchase = new Purchase($transaction);

                if(is_int($transaction->product_id)) $transaction->product_id = ProductType::tryFrom($transaction->product_id);

                match ($transaction->product_id) {
                    ProductType::AIRTIME => $purchase->airtime(),
                    ProductType::UTILITY => $purchase->utility(),
                    ProductType::SUBSCRIPTION => $purchase->subscription(),
                    ProductType::VOUCHER => $purchase->voucher($paymentsData),
                    default => throw new Exception("Invalid product purchase!"),
                };
            }
        } catch (Exception $err) {
            Log::error($err);
        }
    }

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function createWithdrawalTransactions(array $transactionsData, $data): Collection
    {
        $transactions = collect();
        foreach($transactionsData as $transactionData) {
            $transactions->add(Transaction::create($transactionData));
        }

        return self::initiateSavingsWithdrawal($transactions, $data);
    }

    /**
     * @throws \Throwable
     */
    public static function initiateSavingsWithdrawal(Collection $transactions, array $data): Collection
    {
        $responses = SidoohSavings::withdrawEarnings($transactions, $data['method']);

        $transactions->each(function($tx) use ($responses) {
            if(array_key_exists($tx->id, $responses['failed'])) {
                $response = $responses['failed'][$tx->id];

                SavingsTransaction::create([
                    'transaction_id' => $tx->id,
                    'description'    => $response,
                    'type'           => TransactionType::DEBIT,
                    'amount'         => $tx->amount,
                    'status'         => Status::FAILED
                ]);

                $tx->status = Status::FAILED;
                $tx->save();
            }

            if(array_key_exists($tx->id, $responses['completed'])) {
                $response = $responses['completed'][$tx->id];

                SavingsTransaction::create([
                    ...$response,
                    'reference'      => $response['id'],
                    'transaction_id' => $tx->id,
                    'id'             => null,
                ]);

                $acc = EarningAccount::withdrawal()->accountId($tx->account_id)->first();
                $acc->update(['self_amount' => $acc->self_amount + $tx->amount]);
                $tx->refresh();
            }
        });

        return $transactions;
    }

    public static function handleFailedTransactionPayments(Collection $transactions, Collection $failedPayments): void
    {
        $transactions->each(function ($transaction) use ($failedPayments) {
            $transaction->payment->update(["status" => Status::FAILED]);
            $transaction->status = Status::FAILED;
            $transaction->save();

            $result = $failedPayments->firstWhere('id', $transaction->payment->payment_id);

            $message = match ($result['stk_result_code']) {
                1 => "You have insufficient Mpesa Balance for this transaction. Kindly top up your Mpesa and try again.",
                default => "Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.",
            };

            $account = SidoohAccounts::find($transaction->account_id);

            SidoohNotify::notify([$account['phone']], $message, EventType::PAYMENT_FAILURE);
        });
    }
}
