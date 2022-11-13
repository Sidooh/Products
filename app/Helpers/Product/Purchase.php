<?php

namespace App\Helpers\Product;

use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentSubtype;
use App\Enums\Status;
use App\Events\SubscriptionPurchaseFailedEvent;
use App\Events\SubscriptionPurchaseSuccessEvent;
use App\Events\VoucherPurchaseEvent;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use App\Services\SidoohPayments;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;
use Throwable;
use function config;

class Purchase
{
    public function __construct(public Transaction $transaction)
    {
    }

    /**
     * @throws Exception
     */
    public function utility(): void
    {
        $provider = explode(' - ', $this->transaction->description)[1];

        match (config('services.sidooh.utilities_provider')) {
            'KYANDA' => KyandaApi::bill($this->transaction, $provider),
            'TANDA' => TandaApi::bill($this->transaction, $provider),
            default => throw new Exception('No provider provided for utility purchase')
        };
    }

    /**
     * @throws \Throwable
     */
    public function airtime(): void
    {
//        TODO: Notify admins of possible duplicate
        if ($this->transaction->atAirtimeRequest || $this->transaction->kyandaTransaction || $this->transaction->tandaRequest) {
            SidoohNotify::notify(admin_contacts(), "ERROR:AIRTIME\n{$this->transaction->id}\nPossible duplicate airtime request... Confirm!!!", EventType::ERROR_ALERT);
            Log::error('Possible duplicate airtime request... Confirm!!!');
            exit;
        }

        $phone = PhoneNumber::make($this->transaction->destination, 'KE')->formatE164();

        match (config('services.sidooh.utilities_provider')) {
            'AT' => AfricasTalkingApi::airtime($this->transaction, $phone),
            'KYANDA' => KyandaApi::airtime($this->transaction, $phone),
            'TANDA' => TandaApi::airtime($this->transaction, $phone),
            default => throw new Exception('No provider provided for airtime purchase')
        };
    }

    /**
     * @throws Throwable
     */
    public function subscription(): ?Subscription
    {
        Log::info('...[INTERNAL - PRODUCT]: Subscribe...');

        if (Subscription::active($this->transaction->account_id)) {
            // TODO: Handle for subscription failure.
            //       Also, should we not check this during the initial API call and reject it?
            SubscriptionPurchaseFailedEvent::dispatch($this->transaction);

            return null;
        }

        $type = SubscriptionType::wherePrice($this->transaction->amount)->firstOrFail();

        $subscription = [
            'status'     => Status::ACTIVE,
            'account_id' => $this->transaction->account_id,
            'start_date' => now(),
            'end_date'   => now()->addMonths($type->duration),
        ];

        return DB::transaction(function () use ($type, $subscription) {
            $sub = $type->subscription()->create($subscription);

            $this->transaction->status = Status::COMPLETED;
            $this->transaction->save();

            SubscriptionPurchaseSuccessEvent::dispatch($sub, $this->transaction);

            return $sub;
        });
    }

    /**
     * @param array $paymentsData
     * @throws \Throwable
     * @deprecated
     */
    public function voucher(array $paymentsData): void
    {
        Log::info('...[INTERNAL - PRODUCT]: Voucher...');

        $this->transaction->status = Status::COMPLETED;
        $this->transaction->save();

        $vouchers = [];
        if (isset($paymentsData['debit_voucher'])) {
            $vouchers[] = $paymentsData['debit_voucher'];
        }
        $vouchers[] = $paymentsData['credit_vouchers'][0];

        // TODO: Disparity, what if multiple payments? Only single transaction is passed here...!
        VoucherPurchaseEvent::dispatch($this->transaction, $vouchers);
    }

    public function voucherV2(): void
    {
        Log::info('...[INTERNAL - PRODUCT]: Voucher V2...');

        $this->transaction->status = Status::COMPLETED;
        $this->transaction->save();

        $creditVoucher = SidoohPayments::findVoucher($this->transaction->payment->extra['voucher_id'], true);

        if (PaymentSubtype::from($this->transaction->payment->subtype) === PaymentSubtype::VOUCHER) {
            $debitVoucher = SidoohPayments::findVoucher($this->transaction->payment->extra['debit_account'], true);
        }
        //        // TODO: Add V2 function that fetches vouchers used
        $vouchers = [
            'debit_voucher'  => $debitVoucher ?? null,
            'credit_voucher' => $creditVoucher,
        ];

        Log::info('...[INTERNAL - PRODUCT]: Voucher V2...', $vouchers);

        // TODO: Disparity, what if multiple payments? Only single transaction is passed here...!
        VoucherPurchaseEvent::dispatch($this->transaction, $vouchers);
    }

    /**
     * @throws Throwable
     */
    public function merchant(): void
    {
        Log::info('...[INTERNAL - PRODUCT]: Merchant...');

//        $this->transaction->update(['status' => Status::COMPLETED]);
        Transaction::updateStatus($this->transaction, Status::COMPLETED);

        $account = SidoohAccounts::find($this->transaction->account_id);

        $destination = $this->transaction->destination;
        $sender = $account['phone'];

        $amount = 'Ksh' . number_format($this->transaction->amount, 2);
        $date = $this->transaction->created_at->timezone('Africa/Nairobi')->format(config('settings.sms_date_time_format'));
        $eventType = EventType::MERCHANT_PAYMENT;

        if ($this->transaction->payment->subtype === PaymentMethod::VOUCHER->name) {
            $method = PaymentMethod::VOUCHER->name;

            $voucher = SidoohPayments::findVoucher($this->transaction->payment->extra['debit_account'], true);
            $bal = 'Ksh' . number_format($voucher['balance'], 2);
            $vtext = " New Voucher balance is $bal.";
        } else {
            $method = $this->transaction->payment->type;
            $vtext = '';

            $extra = $this->transaction->payment->extra;
            if (isset($extra['debit_account']) && $account['phone'] !== $extra['debit_account']) {
                $method = 'OTHER ' . $method;
            }
        }

//        $message = "You have made a payment to Merchant $destination of $amount from your Sidooh account on $date using $method. You have received $userEarnings cashback.$vtext";
        $message = "You have made a payment to Merchant $destination of $amount from your Sidooh account on $date using $method.$vtext";

        SidoohNotify::notify([$sender], $message, $eventType);

    }
}
