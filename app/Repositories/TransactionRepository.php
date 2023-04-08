<?php

namespace App\Repositories;

use App\DTOs\PaymentDTO;
use App\Enums\EarningAccountType;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
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
use DB;
use Error;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
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
    public static function createTransaction(array $data, $extra): Transaction
    {
        $attributes = [
            'account_id'  => $data['account_id'],
            'product_id'  => $data['product_id'],
            'initiator'   => $data['initiator'],
            'type'        => $data['type'],
            'amount'      => $data['amount'],
            'destination' => $data['destination'],
            'description' => $data['description'],
        ];

        if (isset($data['charge'])) {
            $attributes['charge'] = $data['charge'];
        }

        $transaction = Transaction::create($attributes);

        self::initiatePayment($transaction, $data['account'], $extra);

        return $transaction;
    }

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function initiatePayment(Transaction $t, array $account, array $data): void
    {
        $paymentMethod = $data['method'];

        $debitAccount = $data['debit_account'] ?? match ($paymentMethod) {
            PaymentMethod::MPESA   => $account['phone'],
            PaymentMethod::VOUCHER => SidoohPayments::findSidoohVoucherIdForAccount($account['id'])
        };

        $paymentData = new PaymentDTO(
            $t->account_id, $t->amount, $t->description, $t->destination, $paymentMethod, $debitAccount
        );

        if (is_int($t->product_id)) {
            $t->product_id = ProductType::tryFrom($t->product_id);
        }

        match ($t->product_id) {
            ProductType::VOUCHER => $paymentData->setDestination(
                PaymentMethod::VOUCHER,
                SidoohPayments::findSidoohVoucherIdForAccount(SidoohAccounts::findByPhone($t->destination)['id'])
            ),
            ProductType::MERCHANT => $paymentData->setMerchant(
                $data['merchant_type'],
                $data['business_number'],
                $data['account_number'] ?? ''
            ),
            default => $paymentData->setDestination(PaymentMethod::FLOAT, 1)
        };

        try {
            $p = SidoohPayments::requestPayment($paymentData);

            $paymentData = [
                'transaction_id' => $t->id,
                'payment_id'     => $p['id'],
                'amount'         => $p['amount'],
                'charge'         => $p['charge'],
                'type'           => $p['type'],
                'subtype'        => $p['subtype'],
                'status'         => $p['status'],
                'extra'          => [
                    'debit_account' => $debitAccount,
                    ...($p['destination'] ?? []),
                ],
            ];

            Payment::create($paymentData);

            if ($p && $data['method'] === PaymentMethod::VOUCHER && $t->product_id !== ProductType::MERCHANT) {
                self::requestPurchase($t);
            }
        } catch (HttpResponseException) {
            if ($data['method'] === PaymentMethod::VOUCHER) {
                $t->update(['status' => Status::FAILED]);

                $phone = SidoohAccounts::find($t->account_id)['phone'];

                SidoohNotify::notify(
                    [$phone],
                    'Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.',
                    EventType::PAYMENT_FAILURE
                );
            }
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
                ProductType::VOUCHER      => $purchase->voucher(),
                ProductType::MERCHANT     => $purchase->merchant(),
                default                   => throw new Exception('Invalid product purchase!'),
            };
        } catch (Exception $err) {
            Log::error($err);
        }
    }

    /**
     * @throws \Exception
     */
    public static function handleFailedPayment(Transaction $transaction, $payment): void
    {
        $transaction->payment->update(['status' => Status::FAILED]);
        $transaction->status = Status::FAILED;
        $transaction->save();

        if (isset($payment->error_code)) {
            $message = match ($payment->error_code) {
                101 => 'You have insufficient balance for this transaction. Kindly top up your Mpesa and try again.',
                102, 103 => 'Sorry! The mpesa payment request seems to have been cancelled or timed out. Please try again.',
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

        if ($transaction->charge != $transaction->payment->charge) {
            $transaction->update(['charge' => $transaction->payment->charge]);
        }

        TransactionRepository::requestPurchase($transaction);
    }

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function createWithdrawalTransaction(array $transactionData, $data): Transaction
    {
        $attributes = [
            'account_id'  => $transactionData['account_id'],
            'product_id'  => $transactionData['product_id'],
            'initiator'   => $transactionData['initiator'],
            'type'        => $transactionData['type'],
            'amount'      => $transactionData['amount'],
            'destination' => $transactionData['destination'],
            'description' => $transactionData['description'],
            'charge' => $transactionData['charge'],
        ];

        $transaction = Transaction::create($attributes);

        self::initiateSavingsWithdrawal($transaction, $data);

        return $transaction;
    }

    /**
     * @throws \Throwable
     */
    public static function initiateSavingsWithdrawal(Transaction $transaction, array $data): void
    {
        try {
            $response = SidoohSavings::withdrawEarnings($transaction, $data['method']);
        } catch (Exception) {
            $transaction->status = Status::FAILED;
            $transaction->save();

            throw new Error('Something went wrong, please try again later.');
        }

        SavingsTransaction::create([
            'transaction_id' => $transaction->id,
            'savings_id'     => $response['id'],
            'amount'         => $response['amount'],
            'charge'         => $response['charge'] ?? $transaction->charge,
            'description'    => $response['description'],
            'type'           => $response['type'],
            'status'         => $response['status'],
            'extra'          => $response['extra'],
        ]);

        $acc = EarningAccount::firstOrCreate([
            'type'       => EarningAccountType::WITHDRAWALS,
            'account_id' => $transaction->account_id,
        ]);
        $acc->update([
            'self_amount'   => $acc->self_amount + $response['amount'],
            'invite_amount' => $acc->invite_amount + $response['charge'],
        ]);

        $tagline = config('services.sidooh.tagline');
        $message = "Your withdrawal request has been received. Please be patient as we review it.\n\n$tagline";

        $account = SidoohAccounts::find($transaction->account_id);

        SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_PAYMENT);
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     */
    public static function handleFailedWithdrawal(Transaction $transaction): void
    {
        DB::transaction(function() use ($transaction) {
            $transaction->savingsTransaction->update(['status' => Status::FAILED]);

            $acc = EarningAccount::accountId($transaction->account_id)->withdrawal()->first();
            $acc->update([
                'self_amount'   => $acc->self_amount - $transaction->amount,
                'invite_amount' => $acc->invite_amount - $transaction->savingsTransaction->charge,
            ]);

            $transaction->status = Status::FAILED;
            $transaction->save();
        });

        $message = "Hi, we have refunded Ksh$transaction->amount to your earnings because we could not complete your withdrawal request. We apologize for the inconvenience. Please try again.";

        $account = SidoohAccounts::find($transaction->account_id);

        SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_FAILURE);
    }

    /**
     * @throws Throwable
     */
    public static function handleCompletedWithdrawal(Transaction $transaction): void
    {
        $transaction->savingsTransaction->update(['status' => Status::COMPLETED]);

        $transaction->status = Status::COMPLETED;
        $transaction->save();

        $destination = $transaction->savingsTransaction->extra['destination'];
        $account = $transaction->savingsTransaction->extra['destination_account'];
        $message = "Congrats! Your have withdrawn $transaction->amount points from your earnings account to $destination - $account successfully.\n";
        $message .= config('services.sidooh.tagline');

        $account = SidoohAccounts::find($transaction->account_id);

        SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_PAYMENT);
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
     * @throws \Exception
     */
    public static function refundTransaction(Transaction $transaction): void
    {
        $phone = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $destination = $transaction->destination;
        $date = $transaction->updated_at->timezone('Africa/Nairobi')->format(config('settings.sms_date_time_format'));

        $provider = getProviderFromTransaction($transaction);

        $voucher = credit_voucher($transaction);

        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $amount = 'Ksh'.number_format($amount, 2);
        $balance = 'Ksh'.number_format($voucher['balance']);

        $message = match ($transaction->product_id) {
            ProductType::AIRTIME->value => "Hi, we have added $amount to your voucher account because we could not complete your $amount airtime purchase for $destination on $date. New voucher balance is $balance. Use it in your next purchase.",
            ProductType::UTILITY->value => "Hi, we have added $amount to your voucher account because we could not complete your payment to $provider of $amount for $destination on $date. New voucher balance is $balance. Use it in your next purchase."
        };

        SidoohNotify::notify([$phone], $message, EventType::VOUCHER_REFUND);
    }
}
