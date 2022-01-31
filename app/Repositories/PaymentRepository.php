<?php

namespace App\Repositories;

use App\Enums\MpesaReference;
use App\Enums\PaymentSubtype;
use App\Enums\PaymentType;
use App\Enums\Status;
use App\Enums\VoucherType;
use App\Models\SubscriptionType;
use App\Models\Voucher;
use DrH\Mpesa\Exceptions\MpesaException;
use Exception;
use Illuminate\Support\Facades\Log;
use JetBrains\PhpStorm\ArrayShape;
use Propaganistas\LaravelPhone\PhoneNumber;

class PaymentRepository
{
    private $amount, $product, $phone;

    public function mpesa($targetNumber = null, $mpesaNumber = null): ?array
    {
        $purchaseDesc = "Airtime Purchase";
        if($this->product === 'voucher') $purchaseDesc = "Voucher Purchase";

        $description = $targetNumber
            ? "$purchaseDesc - $targetNumber"
            : $purchaseDesc;
        $number = $mpesaNumber ?? $this->phone;

        try {
            $stkResponse = mpesa_request($number, $this->amount, MpesaReference::AIRTIME, $description);
        } catch (MpesaException $e) {
//            TODO: Inform customer of issue?
            Log::critical($e);
            return null;
        }

        return [
            'amount'        => $this->amount,
            'status'        => Status::PENDING,
            'type'          => PaymentType::MOBILE,
            'subtype'       => PaymentSubtype::STK,
            'provider_id'   => $stkResponse->id,
            'provider_type' => $stkResponse->getMorphClass(),
            'phone'         => $targetNumber
                ? PhoneNumber::make($targetNumber, 'KE')->formatE164()
                : $this->phone,
        ];
    }

    /**
     * @throws Exception
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
    public function voucher($account, $destination = null): array
    {
        $voucher = Voucher::firstOrCreate(['account_id' => $account['id']], [
            ...$account,
            'type' => VoucherType::SIDOOH
        ]);

        if($voucher) {
            $bal = $voucher->balance;

            if($bal < (int)$this->amount) throw new Exception("Insufficient voucher balance!");
        }

        $voucher->balance -= $this->amount;
        $voucher->save();

        $paymentData = [
            'amount'        => $this->amount,
            'type'          => PaymentType::SIDOOH,
            'subtype'       => PaymentSubtype::VOUCHER,
            'status'        => Status::COMPLETED,
            'provider_id'   => $voucher->id,
            'provider_type' => $voucher->getMorphClass(),
        ];

        if($this->product === 'subscription') {
            $paymentData['amount'] = SubscriptionType::wherePrice($this->amount)->firstOrFail()->value('price');
            $paymentData['status'] = Status::PENDING;
        } else if($this->product === 'airtime') {
            $paymentData['phone'] = $destination
                ? PhoneNumber::make($destination, 'KE')->formatE164()
                : $this->phone;
        } else if($this->product === 'utility') {
            $paymentData['account_number'] = $destination;
        }

        return $paymentData;
    }

    /**
     * @param mixed $amount
     * @return PaymentRepository
     */
    public function setAmount(mixed $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param string $product
     */
    public function setProduct(string $product): void
    {
        $this->product = $product;
    }

    /**
     * @param string $phone
     * @return PaymentRepository
     */
    public function setAccountId(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }
}
