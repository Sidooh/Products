<?php

namespace App\Repositories\V2;

use App\DTOs\PaymentDTO;
use App\Enums\Description;
use App\Enums\EarningAccountType;
use App\Enums\EventType;
use App\Enums\MerchantType;
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
use Illuminate\Http\Request;
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
    public static function createTransaction(array $transactionData, $data): Transaction
    {
        $transaction = Transaction::create($transactionData);

        self::initiatePayment($transaction, $transactionData['account'], $data);

        return $transaction;
    }

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function initiatePayment(Transaction $t, array $account, array $data): void
    {
        $paymentMethod = $data['method'];

        if (isset($data['debit_account'])) {
            $debit_account = $data['debit_account'];
        } else {
//            $account = SidoohAccounts::find($t->account_id);
            // TODO: Find sidooh voucher for account
            $debit_account = $paymentMethod === PaymentMethod::MPESA ? $account['phone'] : $account['id'];
        }

        $paymentData = new PaymentDTO($t->account_id, $t->amount, $t->description, $t->destination, $paymentMethod, $debit_account);

        if (is_int($t->product_id)) {
            $t->product_id = ProductType::tryFrom($t->product_id);
        }

        match ($t->product_id) {
            ProductType::VOUCHER => $paymentData->setVoucher(SidoohPayments::findSidoohVoucherIdForAccount(SidoohAccounts::findByPhone($t->destination)['id'])),
//            ProductType::WITHDRAWAL => $paymentData->setWithdrawal(),
//            ProductType::MERCHANT => $paymentData->setWithdrawal(),
//            ProductType::FLOAT => $paymentData->setWithdrawal(),
            default => null
        };

        $p = SidoohPayments::requestPayment($paymentData);

        $paymentData = [
            'transaction_id' => $t->id,
            'payment_id'     => $p['id'],
            'amount'         => $p['amount'],
            'type'           => $p['type'],
            'subtype'        => $p['subtype'],
            'status'         => $p['status'],
            'extra'          => [
                'debit_account' => $debit_account,
                ...($p['destination'] ?? []),
            ],
        ];

        Payment::create($paymentData);

        if ($p && $data['method'] === PaymentMethod::VOUCHER) {
            self::requestPurchase($t);
        }
    }

    /**
     * @throws Throwable
     */
    public static function requestPurchase(Transaction $transaction): void
    {
        try {
            $purchase = new Purchase($transaction);

            if (is_int($transaction->product_id)) {
                $transaction->product_id = ProductType::tryFrom($transaction->product_id);
            }

            match ($transaction->product_id) {
                ProductType::AIRTIME      => $purchase->airtime(),
                ProductType::UTILITY      => $purchase->utility(),
                ProductType::SUBSCRIPTION => $purchase->subscription(),
                ProductType::VOUCHER      => $purchase->voucherV2(),
                default                   => throw new Exception('Invalid product purchase!'),
            };
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
     * @throws Throwable
     */
    public static function initiateSavingsWithdrawal(Collection $transactions, array $data): Collection
    {
        $responses = SidoohSavings::withdrawEarnings($transactions, $data['method']);

        $transactions->each(function($tx) use ($responses) {
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

    public static function handleFailedPayment(Transaction $transaction, Request $payment): void
    {
        $transaction->payment->update(['status' => Status::FAILED]);
        $transaction->status = Status::FAILED;
        $transaction->save();

        if ($payment->subtype === PaymentSubtype::STK->name && isset($payment->code)) {
            $message = match ($payment->code) {
                1       => 'You have insufficient Mpesa Balance for this transaction. Kindly top up your Mpesa and try again.',
                default => 'Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.',
            };
        } elseif (ProductType::tryFrom($transaction->product_id) === ProductType::MERCHANT) {
            $destination = $transaction->destination;
            $message = "Sorry! We failed to complete your transaction to merchant: $destination. No amount was deducted from your account. We apologize for the inconvenience. Please try again.";
        } else {
            $message = 'Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.';
        }

        $account = SidoohAccounts::find($transaction->account_id);

        SidoohNotify::notify([$account['phone']], $message, EventType::PAYMENT_FAILURE);
    }

    /**
     * @throws Throwable
     */
    public static function handleCompletedPayment(Transaction $transaction): void
    {
        $transaction->payment->update(['status' => Status::COMPLETED]);

        TransactionRepository::requestPurchase($transaction);
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

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function createB2bTransaction(array $transactionData, array $data): int
    {
        $transaction = Transaction::create($transactionData);

        self::initiateB2bPayment($transaction, $data);

        return $transaction->id;
    }

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function initiateB2bPayment(Transaction $transaction, array $data): void
    {
        if (isset($data['debit_account'])) {
            $debit_account = $data['debit_account'];
        } else {
            $account = $data['payment_account'];
            $debit_account = $data['method'] === PaymentMethod::MPESA ? $account['phone'] : $account['id'];
        }

        $transactionData = [
            'reference'   => $transaction->id,
            'product_id'  => $transaction->product_id,
            'amount'      => $transaction->amount,
            'destination' => $transaction->destination,
            'description' => $transaction->description,
        ];

        $merchantDetails = [
            'merchant_type' => $data['merchant_type'],
        ];

        if ($data['merchant_type'] === MerchantType::MPESA_PAY_BILL) {
            $merchantDetails += [
                'paybill_number' => $data['business_number'],
                'account_number' => $data['account_number'],
            ];
        } else {
            $merchantDetails += [
                'till_number'    => $data['business_number'],
                'account_number' => '',
            ];
        }

        $responseData = SidoohPayments::requestB2bPayment($transactionData, $data['method'], $debit_account, $merchantDetails);

        if (! isset($responseData['payments']) && ! isset($responseData['b2b_payment'])) {
            throw new Exception('Purchase Failed!');
        }

        $p = $responseData['b2b_payment'];
        Payment::insert([
            'transaction_id' => $p['reference'],
            'payment_id'     => $p['id'],
            'amount'         => $p['amount'],
            'type'           => $p['type'],
            'subtype'        => $p['subtype'],
            'status'         => $p['status'],
            'extra'          => json_encode($responseData['debit_voucher'] ?? ['debit_account' => $debit_account]),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }
}
