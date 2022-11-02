<?php

namespace App\Repositories\V2;

use App\DTOs\PaymentDTO;
use App\Enums\EventType;
use App\Enums\MerchantType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSubtype;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Helpers\Product\Purchase;
use App\Models\Payment;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use App\Traits\ApiResponse;
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
            ProductType::MERCHANT => $paymentData->setMerchant($data['merchant_type'], $data['business_number'], $data['account_number'] ?? ''),
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
                ProductType::VOUCHER      => $purchase->voucherV2(),
                ProductType::MERCHANT      => $purchase->merchant(),
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
