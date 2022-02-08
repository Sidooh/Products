<?php

namespace App\Repositories;

use App\Enums\MpesaReference;
use App\Enums\PaymentSubtype;
use App\Enums\PaymentType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Enums\VoucherType;
use App\Models\SubscriptionType;
use App\Models\Transaction;
use App\Models\Voucher;
use DrH\Mpesa\Exceptions\MpesaException;
use Exception;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\ArrayShape;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;

class PaymentRepository
{
    private array $data;
    private Transaction $transaction;

    public function mpesa($targetNumber = null, $mpesaNumber = null)
    {
        $number = $mpesaNumber ?? $this->data['phone'];

        try {
            $stkResponse = mpesa_request($number, $this->data['amount'], MpesaReference::AIRTIME, $this->data['description']);
        } catch (MpesaException $e) {
//            TODO: Inform customer of issue?
            Log::critical($e);
            return null;
        }

        $this->transaction->payment()->create([
            'amount'        => $this->data['amount'],
            'status'        => Status::PENDING,
            'type'          => PaymentType::MOBILE,
            'subtype'       => PaymentSubtype::STK,
            'provider_id'   => $stkResponse->id,
            'provider_type' => $stkResponse->getMorphClass(),
            'phone'         => PhoneNumber::make($targetNumber, 'KE')->formatE164()
        ]);
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    #[ArrayShape([
        'amount'         => "mixed",
        'type'           => "\App\Enums\PaymentType",
        'subtype'        => "\App\Enums\PaymentSubtype",
        'status'         => "\App\Enums\Status",
        'provider_id'    => "mixed",
        'provider_type'  => "mixed",
        'phone'          => "string",
        'account_number' => "mixed|null"
    ])]
    public function voucher($account, $destination)
    {
        $voucher = Voucher::firstOrCreate(['account_id' => $account['id']], [
            ...$account,
            'type' => VoucherType::SIDOOH
        ]);

        if($voucher) {
            $bal = $voucher->balance;

            if($bal < (int)$this->data['amount']) throw new Exception("Insufficient voucher balance!");
        }

        $voucher->balance -= $this->data['amount'];
        $voucher->save();

        $paymentData = [
            'amount'        => $this->data['amount'],
            'type'          => PaymentType::SIDOOH,
            'subtype'       => PaymentSubtype::VOUCHER,
            'status'        => Status::COMPLETED,
            'provider_id'   => $voucher->id,
            'provider_type' => $voucher->getMorphClass(),
        ];

        if($this->data['product'] === 'subscription') {
            $paymentData['amount'] = SubscriptionType::wherePrice($this->data['amount'])->firstOrFail()->value('price');
            $paymentData['status'] = Status::PENDING;
        } else if($this->data['product'] === 'airtime') {
            $paymentData['phone'] = PhoneNumber::make($destination, 'KE')->formatE164();
        } else if($this->data['product'] === 'utility') {
            $paymentData['account_number'] = $destination;
        }

        if($this->data['product'] === 'merchant') $paymentData['status'] = Status::PENDING;

        $voucher->voucherTransaction()->create([
            'amount'      => $this->data['amount'],
            'type'        => TransactionType::DEBIT,
            'description' => $this->data['description']
        ]);

        $this->data += $paymentData;
        $this->transaction->payment()->create($this->data);

        ProductRepository::requestPurchase($this->transaction, $this->data);
    }


    /**
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @param Transaction $transaction
     */
    public function setTransaction(Transaction $transaction): void
    {
        $this->transaction = $transaction;
    }
}
