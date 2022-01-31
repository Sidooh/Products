<?php

namespace App\Helpers\Product;

use App\Enums\Status;
use App\Events\SubscriptionPurchaseEvent;
use App\Events\SubscriptionPurchaseFailedEvent;
use App\Events\VoucherPurchaseEvent;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\Transaction;
use App\Models\Voucher;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;
use function config;

class Purchase
{
    /**
     * @throws Exception
     */
    public function utility(Transaction $transaction, array $billDetails, string $provider): void
    {
        match (config('services.sidooh.utilities_provider')) {
            'KYANDA' => KyandaApi::bill($transaction, $billDetails, $provider),
            'TANDA' => TandaApi::bill($transaction, $billDetails, $provider),
            default => throw new Exception('No provider provided for utility purchase')
        };
    }

    /**
     * @param Transaction $transaction
     * @param array       $airtimeData
     * @throws Throwable
     */
    public function airtime(Transaction $transaction, array $airtimeData): void
    {
        if($transaction->airtime) exit;

        match (config('services.sidooh.utilities_provider')) {
            'AT' => AfricasTalkingApi::airtime($transaction, $airtimeData),
            'KYANDA' => KyandaApi::airtime($transaction, $airtimeData),
            'TANDA' => TandaApi::airtime($transaction, $airtimeData),
            default => throw new Exception('No provider provided for airtime purchase')
        };
    }

    /**
     * @throws Throwable
     */
    public function subscription(Transaction $transaction, int $amount): ?Subscription
    {
        if(Subscription::active($transaction->account_id)) {
            SubscriptionPurchaseFailedEvent::dispatch($transaction);

            return null;
        }

        $type = SubscriptionType::wherePrice($transaction->amount)->firstOrFail();

        $subscription = [
            'amount'     => $amount,
            'active'     => true,
            'account_id' => $transaction->account_id,
            'start_date' => now(),
            'end_date'   => now()->addMonths($type->duration),
        ];

        return DB::transaction(function() use ($type, $subscription, $amount, $transaction) {
            $sub = $type->subscription()->create($subscription);

            $transaction->status = Status::COMPLETED;
            $transaction->save();

            SubscriptionPurchaseEvent::dispatch($sub, $transaction);

            return $sub;
        });
    }

    public function voucher(Transaction $transaction): Model|Builder|Voucher
    {
        $voucher = Voucher::whereAccountId($transaction->account_id)->firstOrFail();

        $voucher->balance += (double)$transaction->amount;
        $voucher->save();

        $transaction->status = Status::COMPLETED;
        $transaction->save();

        VoucherPurchaseEvent::dispatch($voucher, $transaction);

        return $voucher;
    }
}
