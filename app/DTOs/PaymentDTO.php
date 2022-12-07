<?php

namespace App\DTOs;

use App\Enums\Description;
use App\Enums\MerchantType;
use App\Enums\PaymentMethod;

class PaymentDTO
{
    public function __construct(
        public readonly int $account_id,
        public readonly int $amount,
        public readonly Description|string $description,
        public readonly string $reference,
        public readonly PaymentMethod $source,
        public readonly int $source_account
    ) {
        $this->ipn = config('app.url').'/api/sidooh/payments/callback';
        $this->endpoint = '/payments';
    }

    public function setMerchant(MerchantType $merchantType, int $businessNumber, string $account): void
    {
        $this->endpoint = '/payments/merchant';

        $this->merchant_type = $merchantType;

        if ($this->merchant_type === MerchantType::MPESA_PAY_BILL) {
            $this->paybill_number = $businessNumber;
            $this->account_number = $account;
        } else {
            $this->till_number = $businessNumber;
        }
    }

    public function setVoucher(int $voucher): void
    {
        $this->voucher = $voucher;
        $this->endpoint = '/vouchers/credit';
    }

    public function setDestination(PaymentMethod $destination, string $account): void
    {
        $this->destination = $destination;
        $this->destination_account = $account;
    }

    public function setSource(PaymentMethod $destination, string $account): void
    {
        $this->destination = $destination;
        $this->destination_account = $account;
    }
}
