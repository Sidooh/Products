<?php

namespace App\Helpers\Product;

use App\Enums\Status;
use App\Events\MerchantPurchaseEvent;
use App\Events\SubscriptionPurchaseEvent;
use App\Events\SubscriptionPurchaseFailedEvent;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\Transaction;
use App\Models\Voucher;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;
use function config;

class Purchase
{
    public function __construct(public Transaction $transaction) { }


    /**
     * @throws Exception
     */
    public function utility(array $billDetails, string $provider): void
    {
        match (config('services.sidooh.utilities_provider')) {
            'KYANDA' => KyandaApi::bill($this->transaction, $billDetails, $provider),
            'TANDA' => TandaApi::bill($this->transaction, $billDetails, $provider),
            default => throw new Exception('No provider provided for utility purchase')
        };
    }

    /**
     * @param array $airtimeData
     * @throws Throwable
     */
    public function airtime(array $airtimeData): void
    {
        if($this->transaction->airtime) exit;

        match (config('services.sidooh.utilities_provider')) {
            'AT' => AfricasTalkingApi::airtime($this->transaction, $airtimeData),
            'KYANDA' => KyandaApi::airtime($this->transaction, $airtimeData),
            'TANDA' => TandaApi::airtime($this->transaction, $airtimeData),
            default => throw new Exception('No provider provided for airtime purchase')
        };
    }

    /**
     * @throws Throwable
     */
    public function subscription(int $amount): ?Subscription
    {
        if(Subscription::active($this->transaction->account_id)) {
            SubscriptionPurchaseFailedEvent::dispatch($this->transaction);

            return null;
        }

        $type = SubscriptionType::wherePrice($this->transaction->amount)->firstOrFail();

        $subscription = [
            'amount'     => $amount,
            'active'     => true,
            'account_id' => $this->transaction->account_id,
            'start_date' => now(),
            'end_date'   => now()->addMonths($type->duration),
        ];

        return DB::transaction(function() use ($type, $subscription, $amount) {
            $sub = $type->subscription()->create($subscription);

            $this->transaction->status = Status::COMPLETED;
            $this->transaction->save();

            SubscriptionPurchaseEvent::dispatch($sub, $this->transaction);

            return $sub;
        });
    }
}
