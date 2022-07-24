<?php

namespace App\Helpers\Product;

use App\Enums\EventType;
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
use App\Services\SidoohNotify;
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
            SidoohNotify::notify([
                '254714611696',
                '254711414987',
                '254721309253'
            ], "ERROR:AIRTIME\n{$this->transaction->id}", EventType::ERROR_ALERT);
            Log::info("Possible duplicate airtime request... Confirm!!!");
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
            'amount' => $this->transaction->amount,
            'status' => Status::ACTIVE,
            'account_id' => $this->transaction->account_id,
            'start_date' => now(),
            'end_date' => now()->addMonths($type->duration),
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
     */
    public function voucher(array $paymentsData): void
    {
        Log::info('...[INTERNAL - PRODUCT]: Voucher...');

        $this->transaction->status = Status::COMPLETED;
        $this->transaction->save();

        $vouchers = [];
        if (isset($paymentsData['debit_voucher'])) $vouchers[] = $paymentsData['debit_voucher'];
        $vouchers[] = $paymentsData['credit_vouchers'][0];

        // TODO: Disparity, what if multiple payments? Only single transaction is passed here...!
        VoucherPurchaseEvent::dispatch($this->transaction, $vouchers);
    }
}
