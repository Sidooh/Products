<?php

namespace App\Repositories;

use App\Enums\PaymentSubtype;
use App\Enums\PaymentType;
use App\Enums\Status;
use App\Enums\VoucherType;
use App\Helpers\Sidooh\USSD\Entities\MpesaReferences;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;

class PaymentRepository
{
    private $amount;
    private $phone;

    public function mpesa($targetNumber = null, $mpesaNumber = null)
    {
        $description = $targetNumber
            ? "Airtime Purchase - $targetNumber"
            : "Airtime Purchase";
        $number = $mpesaNumber ?? $this->phone;

        try {
            $stkResponse = mpesa_request($number, $this->amount, MpesaReferences::AIRTIME, $description);
        } catch (MpesaException $e) {
//            TODO: Inform customer of issue?
            Log::critical($e);
            return;
        }

//        error_log(json_encode($stkResponse));

        $accountRep = new AccountRepository();
        $account = $accountRep->create([
            'phone' => $this->phone
        ]);

        $productRep = new ProductRepository();
        $product = $productRep->store(['name' => 'Airtime']);

        $transaction = new Transaction();

        $transaction->amount = $this->amount;
        $transaction->type = 'PAYMENT';
        $transaction->description = $targetNumber
            ? "Airtime Purchase - $targetNumber"
            : "Airtime Purchase";
        $transaction->account_id = $account->id;
        $transaction->product_id = $product->id;

        $transaction->save();

        $payment = new Payment([
            'amount'     => $this->amount,
            'status'     => 'Pending',
            'type'       => 'MPESA',
            'subtype'    => 'STK',
            'payment_id' => $stkResponse->id
        ]);

        $transaction->payment()->save($payment);

        return $stkResponse;
    }

    /**
     * @throws Exception
     */
    public function voucher($targetNumber = null)
    {
        $account = SidoohAccounts::findOrCreate($this->phone);
        $voucher = Voucher::firstOrCreate(['account_id' => $account['id']], [
            ...$account,
            'type' => VoucherType::SIDOOH
        ]);

        if($voucher) {
            $bal = $voucher->balance;

            if($bal === 0 || $bal < (int)$this->amount) {
                return;
            }
        }

        $voucher->balance -= $this->amount;
        $voucher->save();

        return [
            'amount'        => $this->amount,
            'status'        => Status::COMPLETED,
            'type'          => PaymentType::SIDOOH,
            'subtype'       => PaymentSubtype::VOUCHER,
            'provider_id'   => $voucher->id,
            'provider_type' => $voucher->getMorphClass(),
            'phone'         => $targetNumber
                ? PhoneNumber::make($targetNumber, 'KE')->formatE164()
                : $this->phone,
        ];
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }
}
