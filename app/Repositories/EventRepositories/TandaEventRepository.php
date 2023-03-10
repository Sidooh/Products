<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Events\TransactionSuccessEvent;
use App\Models\Transaction;
use App\Repositories\EarningRepository;
use App\Repositories\ProductRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\Providers;
use DrH\Tanda\Models\TandaRequest;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class TandaEventRepository
{
    public static function getProvider(TandaRequest $tandaRequest, Transaction $transaction)
    {
        $provider = $tandaRequest->provider;

        if (empty($provider)) {
            $productId = $transaction->product_id;
            $descriptionArray = explode(' ', $transaction->description);

            $provider = $productId == ProductType::AIRTIME->value ? getTelcoFromPhone($transaction->destination)
                : $descriptionArray[0];

            $tandaRequest->provider = $provider;

            if (empty($tandaRequest->destination)) {
                $tandaRequest->destination = $transaction->destination;
            }

            $tandaRequest->save();
        }

        return $provider;
    }

    public static function requestSuccess(TandaRequest $tandaRequest): void
    {
        // Update Transaction
        if ($tandaRequest->relation_id) {
            $transaction = Transaction::find($tandaRequest->relation_id);
        } else {
            $transaction = Transaction::whereStatus(Status::PENDING->name)->whereType(TransactionType::PAYMENT->name)
                                      ->whereAmount($tandaRequest->amount)->where(
                                          'destination',
                                          'LIKE',
                                          '%'.$tandaRequest->destination
                                      )->whereDate('createdAt', '<', $tandaRequest->created_at);
            $tandaRequest->relation_id = $transaction->id;
            $tandaRequest->save();
        }

        if ($transaction->status == Status::COMPLETED->value) {
            SidoohNotify::notify(
                admin_contacts(),
                "ERROR:TANDA REQUEST\nTransaction $transaction->id seems to have been completed already. Confirm!!!",
                EventType::ERROR_ALERT
            );

            return;
        }

        if ($transaction->status != Status::PENDING->value) {
            SidoohNotify::notify(
                admin_contacts(),
                "ERROR:TANDA REQUEST\nTransaction $transaction->id is not pending. Confirm!!!",
                EventType::ERROR_ALERT
            );

            return;
        }

        $provider = self::getProvider($tandaRequest, $transaction);
        // Handle for KPLC provider :: https://products-dashboard-zwwy5he2ia-uc.a.run.app/transactions/13159

        $account = SidoohAccounts::find($transaction->account_id);

        if ($transaction->payment->subtype === PaymentMethod::VOUCHER->name) {
            $method = PaymentMethod::VOUCHER->name;

            $voucher = SidoohPayments::findVoucher($transaction->payment->extra['debit_account']);
            $bal = 'Ksh'.number_format($voucher['balance'], 2);
            $vtext = " New Voucher balance is $bal.";
        } else {
            $method = $transaction->payment->type;
            $vtext = '';

            $extra = $transaction->payment->extra;
            if (isset($extra['debit_account']) && $account['phone'] !== $extra['debit_account']) {
                $method = 'OTHER '.$method;
            }
        }

        $code = config('services.at.ussd.code');

        $destination = $transaction->destination;
        $sender = $account['phone'];

        $amount = 'Ksh'.number_format($transaction->amount, 2);
        $date = $transaction->created_at->timezone('Africa/Nairobi')->format(config('settings.sms_date_time_format'));
        $eventType = EventType::UTILITY_PAYMENT;

        $rateConfig = config("services.tanda.discounts.$provider", ['type' => '$', 'value' => 0]);
        $totalEarnings = match ($rateConfig['type']) {
            '%' => $rateConfig['value'] * $transaction->amount,
            '$' => $rateConfig['value']
        };
        if ($totalEarnings <= 0) {
            Log::error('...[REP - TANDA]: New Calculation... Failed!!!', [$rateConfig, $totalEarnings]);

            return;
        }

        switch($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
                //  Get Points Earned
                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);
                $phone = ltrim(phone($destination, 'KE')->formatE164(), '+');
                $eventType = EventType::AIRTIME_PURCHASE;

                //  Send SMS
                if ($phone != $sender) {
                    $message = "You have purchased $amount airtime for $phone from your Sidooh account on $date using $method. You have received $userEarnings points.$vtext";

                    SidoohNotify::notify([$sender], $message, $eventType);

                    $message = "Congratulations! You have received $amount airtime from Sidooh account $sender on $date. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";
                } else {
                    $message = "You have purchased $amount airtime from your Sidooh account on $date using $method. You have received $userEarnings points.$vtext";
                }

                $sender = $phone;
                break;
//            case Providers::KPLC_POSTPAID:
//                //  Get Points Earned
//                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);
//
//                //  Send SMS
//                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings points.$vtext";
//                break;
            case Providers::KPLC_PREPAID:
                //  Get Points Earned
                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);

                ['Token' => $tokens, 'Units' => $units] = array_column($tandaRequest->result, 'value', 'label');

                //  Send SMS
                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings points.$vtext";
                $message .= "\nTokens: $tokens\nUnits: $units";
                break;
            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:
            case Providers::KPLC_POSTPAID:
            case Providers::NAIROBI_WTR:
                //  Get Points Earned
                $userEarnings = EarningRepository::getPointsEarned($transaction, $totalEarnings);

                //  Send SMS
                $message = "You have made a payment to $provider - $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings points.$vtext";
                break;
            default:
                throw new Exception('Tanda Request Failure: Provider is Non-existent.');
        }

        //  Update Transaction & Earnings
        TransactionSuccessEvent::dispatch($transaction, $totalEarnings);
        ProductRepository::syncAccounts($account, $provider, $destination);
        SidoohNotify::notify([$sender], $message, $eventType);

        Log::info('...[REP - TANDA]: Completed Transaction...');
    }

    /**
     * @throws RequestException|AuthenticationException|Exception
     */
    public static function requestFailed(TandaRequest $tandaRequest): void
    {
        // Update Transaction
        $transaction = Transaction::find($tandaRequest->relation_id);

        if ($transaction->status != Status::PENDING->value) {
            SidoohNotify::notify(
                admin_contacts(),
                "ERROR:TANDA REQUEST\nTransaction $transaction->id is not pending. Confirm!!!",
                EventType::ERROR_ALERT
            );

            return;
        }

        $destination = $transaction->destination;
        $sender = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $date = $transaction->created_at->timezone('Africa/Nairobi')->format(config('settings.sms_date_time_format'));

        $provider = self::getProvider($tandaRequest, $transaction);

        // Perform Refund
        $voucher = credit_voucher($transaction);

        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $amount = 'Ksh'.number_format($amount, 2);
        $balance = 'Ksh'.number_format($voucher['balance']);

        $message = match ($transaction->product_id) {
            ProductType::AIRTIME->value => "Hi, we have added $amount to your voucher account because we could not complete your $amount airtime purchase for $destination on $date. New voucher balance is $balance. Use it in your next purchase.",
            ProductType::UTILITY->value => "Hi, we have added $amount to your voucher account because we could not complete your payment to $provider of $amount for $destination on $date. New voucher balance is $balance. Use it in your next purchase."
        };

        $event = match ($transaction->product_id) {
            ProductType::AIRTIME->value => EventType::AIRTIME_PURCHASE_FAILURE,
            ProductType::UTILITY->value => EventType::UTILITY_PAYMENT_FAILURE
        };

        SidoohNotify::notify([$sender], $message, $event);

        SidoohNotify::notify(admin_contacts(), "ERR:TANDA\n$transaction->id\n$sender\n$tandaRequest->message", $event);
    }
}
