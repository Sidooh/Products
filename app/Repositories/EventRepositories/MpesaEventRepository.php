<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Enums\MpesaReference;
use App\Enums\Status;
use App\Models\Payment;
use App\Repositories\TransactionRepository;
use App\Services\SidoohNotify;
use Throwable;

class MpesaEventRepository extends EventRepository
{
    public static function stkPaymentFailed($stkCallback)
    {
        // TODO: Make into a transaction/try catch?
        $p = Payment::whereProviderId($stkCallback->request->id)->whereSubtype('STK')->firstOrFail();

        if($p->status == 'FAILED') return;

        $p->status = Status::FAILED;
        $p->save();

        $p->payable->status = Status::FAILED;
        $p->payable->save();

        //  TODO: Can we inform the user of the actual issue?
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

        TransactionRepository::requestPurchase($p->payable, $purchaseData);
    }
}
