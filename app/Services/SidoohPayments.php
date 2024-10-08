<?php

namespace App\Services;

use App\DTOs\PaymentDTO;
use Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    public static function baseUrl()
    {
        return config('services.sidooh.services.payments.url');
    }

    public static function getAll(): array
    {
        Log::info('...[SRV - PAYMENTS]: Get All...');

        $url = self::baseUrl().'/payments';

        return Cache::remember('all_payments', (60 * 60 * 24), fn () => parent::fetch($url));
    }

    /**
     * @throws \Exception
     */
    public static function requestPayment(PaymentDTO $paymentData): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Request Payment...');

        $endpoint = self::baseUrl().$paymentData->endpoint;

        return parent::fetch($endpoint, 'POST', (array) $paymentData);
    }

    /**
     * @throws \Exception
     */
    public static function find(int $paymentId): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Find Payment...');

        return parent::fetch(self::baseUrl()."/payments/$paymentId");
    }

    /**
     * @throws \Exception
     */
    public static function findVoucher(int $voucherId, bool $bypassCache = false): ?array
    {
        $cacheKey = "vouchers.$voucherId";

        if ($bypassCache) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, (60 * 60 * 24), function() use ($voucherId) {
            return parent::fetch(self::baseUrl()."/vouchers/$voucherId");
        });
    }

    // TODO: Add by voucher type filter
    public static function findVouchersByAccount(int $accountId): Collection
    {
        return Cache::remember($accountId.'_vouchers', (60 * 60 * 3), function() use ($accountId) {
            return collect(parent::fetch(self::baseUrl()."/vouchers?account_id=$accountId"));
        });
    }

    // TODO: Add by voucher type filter
    /**
     * @throws \Exception
     */
    public static function findSidoohVoucherIdForAccount(int $accountId): ?int
    {
        Log::info('...[SRV - PAYMENTS]: Find Sidooh Voucher...', [$accountId]);

        $sidoohVoucher = self::findVouchersByAccount($accountId)
            ->first(fn ($v) => $v['voucher_type_id'] === self::getSidoohVoucherType());

        if (! $sidoohVoucher) {
            $sidoohVoucher = parent::fetch(self::baseUrl().'/vouchers', 'POST', [
                'voucher_type_id'  => self::getSidoohVoucherType(),
                'account_id'       => $accountId,
            ]);
        }

        return $sidoohVoucher['id'];
    }

    private static function getSidoohVoucherType(): int
    {
        return 1;
    }

    public static function getPayBillCharge(int $amount): int
    {
        Log::info('...[SRV - PAYMENTS]: Get PayBill Charge...', [$amount]);

        $charges = Cache::remember('pay_bill_charges', (3600 * 24 * 90), function() {
            return parent::fetch(self::baseUrl().'/charges/pay-bill');
        });

        return Arr::first($charges, fn ($ch) => $ch['max'] >= $amount && $ch['min'] <= $amount, ['charge' => 0])['charge'];
    }

    public static function getBuyGoodsCharge(int $amount): int
    {
        Log::info('...[SRV - PAYMENTS]: Get Buy Goods Charge...', [$amount]);

        $charges = Cache::remember('buy_goods_charges', (3600 * 24 * 90), function() {
            return parent::fetch(self::baseUrl().'/charges/buy-goods');
        });

        return Arr::first($charges, fn ($ch) => $ch['max'] >= $amount && $ch['min'] <= $amount, ['charge' => 0])['charge'];
    }
}
