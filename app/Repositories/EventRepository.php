<?php

namespace App\Repositories;

use App\Enums\EventType;
use App\Enums\MpesaReference;
use App\Enums\Status;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Library\Providers;
use Nabcellent\Kyanda\Models\KyandaRequest;
use Nabcellent\Kyanda\Models\KyandaTransaction;
use Throwable;

class EventRepository
{
    public static function kyandaRequest(KyandaRequest $kyandaRequest)
    {
        $transaction = Transaction::find($kyandaRequest->relation_id);
        $date = $kyandaRequest->created_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));
        $account = SidoohAccounts::find($transaction->account_id);
        $phone = ltrim($account['phone'], '+');
        $amount = $transaction->amount;

        if(!in_array($kyandaRequest->status_code, ['0000', '1100'])) {
            try {
                $message = "KY_ERR:{$kyandaRequest->provider}\n{$kyandaRequest->message}\n{$account['phone']} - $date";

                SidoohNotify::notify(['254110039317'], $message, EventType::ERROR_ALERT);
                Log::info("Kyanda Failure SMS Sent");
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }

            $transaction->status = Status::REIMBURSED;
            $transaction->save();

            $voucher = Voucher::whereAccountId($transaction->account_id)->firstOrFail();
            $voucher->balance += $amount;
            $voucher->save();


            $message = match ($kyandaRequest->provider) {
                Providers::SAFARICOM, Providers::AIRTEL, Providers::FAIBA, Providers::EQUITEL, Providers::TELKOM => "Sorry! We could not complete your KES{$amount} airtime purchase on {$date}. We have added KES{$amount} to your voucher. New Voucher balance is {$voucher->balance}.",
                default => "Sorry! We could not complete your KES{$amount} {$kyandaRequest->provider} payment for {$transaction->destination} on {$date}. We have added KES{$amount} to your voucher. New Voucher balance is {$voucher->balance}.",
            };

            SidoohNotify::notify([$phone], $message, EventType::SP_REQUEST_FAILURE);
        }

        switch($kyandaRequest->provider) {
            case Providers::SAFARICOM:
                //Most likely number accessing. Need to bulletproof this logic though by checking number against user
                break;

            case Providers::AIRTEL:
            case Providers::FAIBA:
            case Providers::EQUITEL:
            case Providers::TELKOM:
                (new AccountRepository())->syncAirtimeAccounts($account, $kyandaRequest->provider, $transaction->destination);
                break;

            default:
                (new AccountRepository())->syncUtilityAccounts($account, $kyandaRequest->provider, $transaction->destination);
        }
    }

    public static function kyandaTransactionFailed(KyandaTransaction $kyandaTransaction)
    {
        $transaction = Transaction::find($kyandaTransaction->request->relation_id);
        Transaction::updateStatus($transaction, Status::FAILED);

        $destination = $kyandaTransaction->destination;
        $sender = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $date = $kyandaTransaction->updated_at->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        $provider = $kyandaTransaction->request->provider;

        $voucher = Voucher::whereAccountId($transaction->account_id)->firstOrFail();
        $voucher->balance += $amount;
        $voucher->save();

        $transaction->status = Status::REIMBURSED;
        $transaction->save();

        $eventType = EventType::PAYMENT_FAILURE;

        switch($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
            case Providers::EQUITEL:
                $message = "Sorry! We could not complete your KES{$amount} airtime purchase for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
                $eventType = EventType::AIRTIME_PURCHASE_FAILURE;
                break;

            case Providers::KPLC_POSTPAID:
            case Providers::KPLC_PREPAID:
            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:
            case Providers::NAIROBI_WTR:
            case Providers::FAIBA_B:
                $message = "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher->balance}.";
                $eventType = EventType::UTILITY_PAYMENT_FAILURE;
                break;

            default:
                $message = "Sorry! We could not complete your KES{$amount} {$kyandaTransaction->request->provider} payment for {$transaction->destination} on {$date}. We have added KES{$amount} to your voucher. New Voucher balance is {$voucher->balance}.";
        }

        SidoohNotify::notify([$sender], $message, $eventType);
    }

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

        if($p->status == 'Complete') return;

        $p->status = 'Complete';
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
}
