<?php

namespace App\Helpers\Product;

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
    public function utility(array $billDetails): void
    {
        $billDetails['account_number'] = $this->transaction->destination;
        $provider = explode(' - ', $this->transaction->description)[1];

        match (config('services.sidooh.utilities_provider')) {
            'KYANDA' => KyandaApi::bill($this->transaction, $billDetails, $provider),
            'TANDA' => TandaApi::bill($this->transaction, $billDetails, $provider),
            default => throw new Exception('No provider provided for utility purchase')
        };
    }

    /**
     * @throws \Throwable
     */
    public function airtime(): void
    {
        if ($this->transaction->airtime) exit;

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
        $this->transaction->status = Status::COMPLETED;
        $this->transaction->save();

        // TODO: Disparity, what if multiple payments? Only single transaction is passed here...!
        VoucherPurchaseEvent::dispatch($this->transaction, $paymentsData['vouchers']);
    }
}
