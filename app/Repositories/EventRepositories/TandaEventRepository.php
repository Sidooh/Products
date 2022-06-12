<?php

namespace App\Repositories\EventRepositories;

use App\Enums\Description;
use App\Enums\EventType;
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
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;

class TandaEventRepository extends EventRepository
{
    public static function getProvider(TandaRequest $tandaRequest, Transaction $transaction)
    {
        $provider = $tandaRequest->provider;

        if (empty($provider)) {
            $productId = $transaction->product_id;
            $descriptionArray = explode(" ", $transaction->description);

            $provider = $productId == ProductType::AIRTIME->value
                ? getTelcoFromPhone($transaction->destination)
                : $descriptionArray[0];

            $tandaRequest->provider = $provider;

            if (empty($tandaRequest->destination)) {
                $tandaRequest->destination = $descriptionArray[1];
            }

            $tandaRequest->save();
        }

        return $provider;
    }

    public static function requestSuccess(TandaRequest $tandaRequest)
    {
        // Update Transaction
        if ($tandaRequest->relation_id) {
            $transaction = Transaction::find($tandaRequest->relation_id);
        } else {
            $transaction = Transaction::whereStatus(Status::PENDING->name)
                ->whereType(TransactionType::PAYMENT->name)
                ->whereAmount($tandaRequest->amount)
                ->whereLike('description', 'LIKE', "%" . $tandaRequest->destination)
                ->whereDate('createdAt', '<', $tandaRequest->created_at);
            $tandaRequest->relation_id = $transaction->id;
            $tandaRequest->save();
        }

        if ($transaction->status == Status::COMPLETED) {
            SidoohNotify::notify([
                '254714611696',
                '254110039317'
            ], "ERROR:TANDA REQUEST\nTransaction ${transaction} seems to have been completed already. Confirm!!!", EventType::ERROR_ALERT);
            return;
        }

        $provider = self::getProvider($tandaRequest, $transaction);

        $account = SidoohAccounts::find($transaction->account_id);

        $paymentDetails = SidoohPayments::findPaymentDetails($transaction->id, $transaction->account_id);
        $payment = $paymentDetails["payment"];
        $voucher = $paymentDetails["voucher"];
        $method = $payment["subtype"];

        if ($method === 'VOUCHER') {
            $bal = $voucher["balance"];
            $vtext = " New Voucher balance is KES$bal.";
        } else {
            $method = 'MPESA';
            $vtext = '';
        }

        $code = config('services.at.ussd.code');

        $destination = $tandaRequest->destination;
        $sender = $account["phone"];

        $amount = $transaction->amount;
        $date = $tandaRequest->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));
        $eventType = EventType::UTILITY_PAYMENT;

        switch ($provider) {
            case Providers::FAIBA:
            case Providers::SAFARICOM:
            case Providers::AIRTEL:
            case Providers::TELKOM:
                //  Get Points Earned
                $totalEarnings = ($provider == Providers::FAIBA
                        ? .07
                        : .06) * $transaction->amount;

                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);
                $phone = ltrim(PhoneNumber::make($destination, 'KE')->formatE164(), '+');
                $eventType = EventType::AIRTIME_PURCHASE;

                //  Send SMS
                if ($phone != $sender) {
                    $message = "You have purchased {$amount} airtime for {$phone} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";

                    SidoohNotify::notify([$sender], $message, $eventType);

                    $message = "Congratulations! You have received {$amount} airtime from Sidooh account {$sender} on {$date}. Sidooh Makes You Money with Every Purchase.\n\nDial $code NOW for FREE on your Safaricom line to BUY AIRTIME & START EARNING from your purchases.";
                } else {
                    $message = "You have purchased {$amount} airtime from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                }

                $sender = $phone;
                break;
            case Providers::KPLC_POSTPAID:
                //  Get Points Earned
                $totalEarnings = .017 * $transaction->amount;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

                //  Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                break;
            case Providers::KPLC_PREPAID:
                //  Get Points Earned
                $totalEarnings = .017 * $transaction->amount;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

                //  Send SMS
                $details = $tandaRequest->result;
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                $message .= "\nTokens: {$details[0]['value']}\nUnits: {$details[1]['value']}";
                break;
            case Providers::DSTV:
            case Providers::GOTV:
            case Providers::ZUKU:
            case Providers::STARTIMES:
                //  Get Points Earned
                $totalEarnings = .003 * $transaction->amount;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

                //  Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                break;
            case Providers::NAIROBI_WTR:
                //  Get Points Earned
                $totalEarnings = 5;
                $userEarnings = EarningRepository::getPointsEarned($totalEarnings);

                //  Send SMS
                $message = "You have made a payment to {$provider} - {$destination} of {$amount} from your Sidooh account on {$date} using $method. You have received {$userEarnings} cashback.$vtext";
                break;
        }

        //  Update Earnings
        TransactionSuccessEvent::dispatch($transaction, $totalEarnings);
        ProductRepository::syncAccounts($account, $provider, $destination);
        SidoohNotify::notify([$sender], $message, $eventType);

        Log::info('--- --- --- --- ---   ...[TANDA EVENT REPOSITORY]: Completed Transaction...   --- --- --- --- ---');
    }

    /**
     * @throws RequestException
     */
    public static function requestFailed(TandaRequest $tandaRequest)
    {
        // Update Transaction
        $transaction = Transaction::find($tandaRequest->relation_id);
        Transaction::updateStatus($transaction, Status::FAILED);

        $destination = $tandaRequest->destination;
        $sender = SidoohAccounts::find($transaction->account_id)['phone'];

        $amount = $transaction->amount;
        $date = $tandaRequest->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $provider = self::getProvider($tandaRequest, $transaction);

        $voucher = SidoohPayments::creditVoucher($transaction->account_id, $amount, Description::VOUCHER_REFUND);

        $transaction->status = Status::REFUNDED;
        $transaction->save();

        $message = match ($provider) {
            Providers::FAIBA, Providers::SAFARICOM, Providers::AIRTEL, Providers::TELKOM => "Sorry! We could not complete your KES{$amount} airtime purchase for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher['balance']}.",
            Providers::KPLC_POSTPAID, Providers::NAIROBI_WTR, Providers::KPLC_PREPAID, Providers::DSTV, Providers::GOTV, Providers::ZUKU, Providers::STARTIMES => "Sorry! We could not complete your payment to {$provider} of KES{$amount} for {$destination} on {$date}. We have added KES{$amount} to your voucher account. New Voucher balance is {$voucher["balance"]}."
        };

        SidoohNotify::notify([$sender], $message, EventType::AIRTIME_PURCHASE_FAILURE);
    }
}
