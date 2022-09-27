<?php

namespace App\Repositories;

use App\Enums\Description;
use App\Enums\EarningAccountType;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSubtype;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\Product\Purchase;
use App\Helpers\Tanda\TandaApi;
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
    public static function initiatePayment(Collection $transactions, array $data): void
    {
        if (isset($data['debit_account'])) {
            $debit_account = $data['debit_account'];
        } else {
            $account = $data['payment_account'];
            $debit_account = $data['method'] === PaymentMethod::MPESA ? $account['phone'] : $account['id'];
        }

        $transactionsData = $transactions->map(fn ($t) => [
            'reference'   => $t->id,
            'product_id'  => $t->product_id,
            'amount'      => $t->amount,
            'destination' => $t->destination,
            'description' => $t->description,
        ]);
        $responseData = SidoohPayments::requestPayment($transactionsData, $data['method'], $debit_account);

        // TODO: Revert this to: if (!isset($response["data"]["payments"])) throw new Exception("Purchase Failed!");
        //  Reason may not be due to payment failure, could be a connection issue etc...
        //  We would then have to manually check. Or implement a query endpoint that polls payment srv at set intervals
        if (! isset($responseData['payments'])) {
//            $transactions->each(fn($t) => $t->update(['status' => Status::FAILED]));

            throw new Exception('Purchase Failed!');
        }

        $paymentData = array_map(function ($p) use ($responseData, $debit_account) {
            return [
                'transaction_id' => $p['reference'],
                'payment_id'     => $p['id'],
                'amount'         => $p['amount'],
                'type'           => $p['type'],
                'subtype'        => $p['subtype'],
                'status'         => $p['status'],
                'extra'          => json_encode($responseData['debit_voucher'] ?? ['debit_account' => $debit_account]),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }, $responseData['payments']);
        Payment::insert($paymentData);

        if ($responseData && $data['method'] === PaymentMethod::VOUCHER) {
            self::requestPurchase($transactions, $responseData);
        }
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Collection $transactions, array $paymentsData): void
    {
        try {
            foreach ($transactions as $transaction) {
                $purchase = new Purchase($transaction);

                if (is_int($transaction->product_id)) {
                    $transaction->product_id = ProductType::tryFrom($transaction->product_id);
                }

                match ($transaction->product_id) {
                    ProductType::AIRTIME      => $purchase->airtime(),
                    ProductType::UTILITY      => $purchase->utility(),
                    ProductType::SUBSCRIPTION => $purchase->subscription(),
                    ProductType::VOUCHER      => $purchase->voucher($paymentsData),
                    default                   => throw new Exception('Invalid product purchase!'),
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
    public static function createWithdrawalTransactions(array $transactionsData, $data): array
    {
        $transactions = collect();
        foreach ($transactionsData as $transactionData) {
            $transactions->add(Transaction::create($transactionData));
        }

        self::initiateSavingsWithdrawal($transactions, $data);

        return Arr::pluck($transactions, 'id');
    }

    /**
     * @throws \Throwable
     */
    public static function initiateSavingsWithdrawal(Collection $transactions, array $data): Collection
    {
        $responses = SidoohSavings::withdrawEarnings($transactions, $data['method']);

        $transactions->each(function ($tx) use ($responses) {
            if (array_key_exists($tx->id, $responses['failed'])) {
                $response = $responses['failed'][$tx->id];

                SavingsTransaction::create([
                    'transaction_id' => $tx->id,
                    'description'    => $response,
                    'type'           => TransactionType::DEBIT,
                    'amount'         => $tx->amount,
                    'status'         => Status::FAILED,
                ]);

                $tx->status = Status::FAILED;
                $tx->save();
            }

            if (array_key_exists($tx->id, $responses['completed'])) {
                $response = $responses['completed'][$tx->id];

                SavingsTransaction::create([
                    ...$response,
                    'reference'      => $response['id'],
                    'transaction_id' => $tx->id,
                    'id'             => null,
                ]);

                //TODO: Fix for new users.
                $acc = EarningAccount::firstOrCreate([
                    'type'       => EarningAccountType::WITHDRAWALS->name,
                    'account_id' => $tx->account_id,
                ]);
                $acc->update(['self_amount' => $acc->self_amount + $tx->amount]);

                $tx->refresh();
            }
        });

        return $transactions;
    }

    public static function handleFailedPayments(Collection $transactions, Collection $failedPayments): void
    {
        $transactions->each(function ($transaction) use ($failedPayments) {
            $transaction->payment->update(['status' => Status::FAILED]);
            $transaction->status = Status::FAILED;
            $transaction->save();

            $result = $failedPayments->firstWhere('id', $transaction->payment->payment_id);

            if ($result['subtype'] === PaymentSubtype::STK->name && isset($result['stk_result_code'])) {
                $message = match ($result['stk_result_code']) {
                    1       => 'You have insufficient Mpesa Balance for this transaction. Kindly top up your Mpesa and try again.',
                    default => 'Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.',
                };
            } else {
                $message = 'Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.';
            }

            $account = SidoohAccounts::find($transaction->account_id);

            SidoohNotify::notify([$account['phone']], $message, EventType::PAYMENT_FAILURE);
        });
    }

    /**
     * @throws \Throwable
     */
    public static function handleCompletedPayments(Collection $transactions, Collection $completedPayments, array $requestData = []): void
    {
        $ids = $completedPayments->pluck('id');

        $payments = Payment::whereIn('payment_id', $ids);
        $payments->update(['status' => Status::COMPLETED]);

        TransactionRepository::requestPurchase($transactions, $requestData);
    }

    public static function checkRequestStatus(Transaction $transaction, string $requestId): void
    {
        match (config('services.sidooh.utilities_provider')) {
//            'AT' => AfricasTalkingApi::airtime($transaction),
//            'KYANDA' => KyandaApi::airtime($transaction),
            'TANDA' => TandaApi::queryStatus($transaction, $requestId)
        };
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException|Exception
     */
    public static function refundTransaction(Transaction $transaction): void
    {
        $phone = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $date = $transaction->updated_at
            ->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        $provider = getProviderFromTransaction($transaction);

        $response = SidoohPayments::creditVoucher($transaction->account_id, $amount, Description::VOUCHER_REFUND);
        [$voucher] = $response['data'];

        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $amount = 'Ksh'.number_format($amount, 2);
        $balance = 'Ksh'.number_format($voucher['balance']);

        $destination = $transaction->destination;

        $message = match ($transaction->product_id) {
            ProductType::AIRTIME->value => "Hi, we have added $amount to your voucher account because we could not complete your $amount airtime purchase for $destination on $date. New voucher balance is $balance.",
            ProductType::UTILITY->value => "Hi, we have added $amount to your voucher account because we could not complete your payment to $provider of $amount for $destination on $date. New voucher balance is $balance."
        };

        SidoohNotify::notify([$phone], $message, EventType::VOUCHER_REFUND);
    }
}
