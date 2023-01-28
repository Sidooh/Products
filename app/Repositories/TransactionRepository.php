<?php

namespace App\Repositories;

use App\DTOs\PaymentDTO;
use App\Enums\Description;
use App\Enums\EarningAccountType;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSubtype;
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
use Error;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
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

        $debit_account = $data['debit_account'] ?? match ($paymentMethod) {
            PaymentMethod::MPESA   => $account['phone'],
            PaymentMethod::VOUCHER => SidoohPayments::findSidoohVoucherIdForAccount($account['id'])
        };

        $paymentData = new PaymentDTO($t->account_id, $t->amount, $t->description, $t->destination, $paymentMethod, $debit_account);

        if (is_int($t->product_id)) {
            $t->product_id = ProductType::tryFrom($t->product_id);
        }

        match ($t->product_id) {
            ProductType::VOUCHER  => $paymentData->setVoucher(SidoohPayments::findSidoohVoucherIdForAccount(SidoohAccounts::findByPhone($t->destination)['id'])),
            ProductType::MERCHANT => $paymentData->setMerchant($data['merchant_type'], $data['business_number'], $data['account_number'] ?? ''),
            default               => $paymentData->setDestination(PaymentMethod::FLOAT, 1)
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

        if ($p && $data['method'] === PaymentMethod::VOUCHER && $t->product_id !== ProductType::MERCHANT) {
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
                ProductType::VOUCHER      => $purchase->voucher(),
                ProductType::MERCHANT     => $purchase->merchant(),
                default                   => throw new Exception('Invalid product purchase!'),
            };
        } catch (Exception $err) {
            Log::error($err);
        }
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

    /**
     * @throws AuthenticationException
     * @throws Throwable
     */
    public static function createWithdrawalTransaction(array $transactionData, $data): Transaction
    {
        $transaction = Transaction::create($transactionData);

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
            'description'    => $response['description'],
            'type'           => $response['type'],
            'status'         => $response['status'],
            'extra'          => $response['extra'],
        ]);

        //TODO: Fix for new users.
        $acc = EarningAccount::firstOrCreate([
            'type'       => EarningAccountType::WITHDRAWALS->name,
            'account_id' => $transaction->account_id,
        ]);
        $acc->increment('self_amount', $transaction->amount);

        $tagline = config('services.sidooh.tagline');
        $message = "Your withdrawal request has been received. Please be patient as we review it.\n\n$tagline";

        $account = SidoohAccounts::find($transaction->account_id);

        SidoohNotify::notify([$account['phone']], $message, EventType::WITHDRAWAL_PAYMENT);
    }

    public static function handleFailedWithdrawal(Transaction $transaction, Request $savings): void
    {
        $transaction->savingsTransaction->update(['status' => Status::FAILED]);

        EarningAccount::accountId($transaction->account_id)
            ->withdrawal()
            ->firstOrCreate()
            ->decrement('self_amount', $transaction->amount);

        $transaction->status = Status::FAILED;
        $transaction->save();

        $message = 'Sorry! We failed to complete your withdrawal request. No amount was deducted from your account. We apologize for the inconvenience. Please try again.';

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
        $date = $transaction->updated_at
            ->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        $provider = getProviderFromTransaction($transaction);

        $voucherId = SidoohPayments::findSidoohVoucherIdForAccount($transaction->account_id);
        $paymentData = new PaymentDTO($transaction->account_id, $amount, Description::VOUCHER_REFUND, $destination, PaymentMethod::FLOAT, 1);
        $paymentData->setVoucher($voucherId);

        SidoohPayments::requestPayment($paymentData);
        $voucher = SidoohPayments::findVoucher($voucherId, true);

        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $amount = 'Ksh'.number_format($amount, 2);
        $balance = 'Ksh'.number_format($voucher['balance']);

        $message = match ($transaction->product_id) {
            ProductType::AIRTIME->value => "Hi, we have added $amount to your voucher account because we could not complete your $amount airtime purchase for $destination on $date. New voucher balance is $balance.",
            ProductType::UTILITY->value => "Hi, we have added $amount to your voucher account because we could not complete your payment to $provider of $amount for $destination on $date. New voucher balance is $balance."
        };

        SidoohNotify::notify([$phone], $message, EventType::VOUCHER_REFUND);
    }
}
