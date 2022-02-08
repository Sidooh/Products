<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Enums\MpesaReference;
use App\Enums\Status;
use App\Enums\VoucherType;
use App\Events\TransactionSuccessEvent;
use App\Models\Merchant;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Repositories\ProductRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use Throwable;

class EventRepository
{
    public static function stkPaymentFailed($stkCallback)
    {
        //        TODO: Make into a transaction/try catch?
        $p = Payment::whereProviderId($stkCallback->request->id)->whereSubtype('STK')->firstOrFail();

        if($p->status == 'FAILED') return;

        $p->status = Status::FAILED;
        $p->save();

        $p->payable->status = Status::FAILED;
        $p->payable->save();

//        TODO: Can we inform the user of the actual issue?
        $message = "Sorry! We failed to complete your transaction. No amount was deducted from your account. We apologize for the inconvenience. Please try again.";

        SidoohNotify::notify([$stkCallback->request->phone], $message, EventType::PAYMENT_FAILURE);
    }

    /**
     * @throws Throwable
     */
    public static function stkPaymentReceived($stkCallback)
    {
        $otherPhone = explode(" - ", $stkCallback->request->description);

        $p = Payment::whereProviderId($stkCallback->request->id)->whereSubtype('STK')->firstOrFail();

        if($p->status == 'COMPLETED') return;

        $p->status = Status::COMPLETED;
        $p->save();

        switch($stkCallback->request->reference) {
            case MpesaReference::AIRTIME:
                $purchaseData['phone'] = count($otherPhone) > 1
                    ? $otherPhone[1]
                    : $stkCallback->PhoneNumber ?? $stkCallback->request->phone;
                $purchaseData['product'] = 'airtime';
                break;

            case MpesaReference::PAY_SUBSCRIPTION:
            case MpesaReference::PRE_AGENT_REGISTER_ASPIRING:
            case MpesaReference::PRE_AGENT_REGISTER_THRIVING:
            case MpesaReference::AGENT_REGISTER_ASPIRING:
            case MpesaReference::AGENT_REGISTER_THRIVING:
            case MpesaReference::AGENT_REGISTER:
                $purchaseData['product'] = 'subscription';
                break;

            case MpesaReference::PAY_VOUCHER:
                $purchaseData['phone'] = count($otherPhone) > 1
                    ? $otherPhone[1]
                    : $stkCallback->PhoneNumber ?? $stkCallback->request->phone;
                $purchaseData['product'] = 'voucher';
                break;

            case MpesaReference::PAY_UTILITY:
                $purchaseData = [
                    'account'  => $otherPhone[1],
                    'provider' => explode(" ", $stkCallback->request->description)[0],
                    'product'  => 'utility'
                ];
                break;
        }

        $purchaseData['amount'] = $stkCallback->Amount;

        ProductRepository::requestPurchase($p->payable, $purchaseData);
    }

    /**
     * @throws Exception
     */
    public static function merchantPurchaseSuccess(Transaction $transaction, Merchant $merchant)
    {
        $amount = $transaction->amount;
        $account = SidoohAccounts::find($transaction->account_id);
        $voucher = Voucher::whereAccountId($account['id'])->whereType(VoucherType::SIDOOH)->firstOrFail();

        $phone = ltrim($account['phone'], '+');
        $mPhone = ltrim($merchant->contact_phone, '+');

        $date = $transaction->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $message = "SIDOOH transaction confirmed. Ksh{$amount} paid to {$merchant->name}";
        $message .= " on {$date}. New VOUCHER balance is {$voucher->balance}.\n\n";
        $message .= "Sidooh, Earns you money on every purchase.";

        SidoohNotify::notify([$phone], $message, EventType::MERCHANT_PAYMENT);

        $message = "SIDOOH transaction confirmed. You have received Ksh{$amount} from \$account->user->name' {$phone}";
        $message .= " on {$date}. New Account balance is {$merchant->balance}.\n\n";
        $message .= "Sidooh, Earns you money on every purchase.";

        SidoohNotify::notify([$mPhone], $message, EventType::MERCHANT_PAYMENT);

        $amount = min($transaction->amount * .025, 250);

        TransactionSuccessEvent::dispatch($transaction, $amount);
    }
}
