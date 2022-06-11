<?php

namespace App\Helpers\Product;

use App\Enums\Status;
use App\Events\SubscriptionPurchaseEvent;
use App\Events\SubscriptionPurchaseFailedEvent;
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
    public function __construct(public Transaction $transaction) { }


    /**
     * @throws Exception
     */
    public function utility(array $billDetails, string $provider): void
    {
        $billDetails['account_number'] = $this->transaction->destination;

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

        $airtimeData['phone'] = PhoneNumber::make($this->transaction->destination, 'KE')->formatE164();

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
    public function subscription(): ?Subscription
    {
        Log::info('--- --- --- --- ---   ...[SIDOOH-API]: Subscribe...   --- --- --- --- ---');

        if(Subscription::active($this->transaction->account_id)) {
            SubscriptionPurchaseFailedEvent::dispatch($this->transaction);

            return null;
        }

        $type = SubscriptionType::wherePrice($this->transaction->amount)->firstOrFail();

        $subscription = [
            'amount'     => $this->transaction->amount,
            'status'     => Status::ACTIVE,
            'account_id' => $this->transaction->account_id,
            'start_date' => now(),
            'end_date'   => now()->addMonths($type->duration),
        ];

        return DB::transaction(function() use ($type, $subscription) {
            $sub = $type->subscription()->create($subscription);

            $this->transaction->status = Status::COMPLETED;
            $this->transaction->save();

            SubscriptionPurchaseEvent::dispatch($sub, $this->transaction);

            return $sub;
        });
    }
}
