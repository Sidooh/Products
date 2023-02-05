<?php

namespace App\Services;

use App\DTOs\PaymentDTO;
use App\Enums\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    public static function getAll(): array
    {
        Log::info('...[SRV - PAYMENTS]: Get All...');

        $url = self::baseUrl().'/payments';

        return Cache::remember('all_payments', (60 * 60 * 24), fn () => parent::fetch($url));
    }

    public static function baseUrl()
    {
        return config('services.sidooh.services.payments.url');
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
    public static function requestB2bPayment(array $transaction, PaymentMethod $method, string $debit_account, array $merchantDetails): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Request B2B Payment...');

        return parent::fetch(self::baseUrl().'/payments/b2b', 'POST', [
            'transactions'  => [$transaction],
            'payment_mode'  => $method->name,
            'debit_account' => $debit_account,
            ...$merchantDetails,
        ]);
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
        $cacheKey = 'vouchers.'.$voucherId;
        $ttl = (60 * 60 * 24);

        if ($bypassCache) {
            $voucher = parent::fetch(self::baseUrl()."/vouchers/$voucherId");
            Cache::put($cacheKey, $voucher, $ttl);

            return $voucher;
        }

        return Cache::remember($cacheKey, $ttl, function() use ($voucherId) {
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

        return $sidoohVoucher['id']; // TODO: don't use magic numbers
    }

    private static function getSidoohVoucherType(): int
    {
        return 1;
    }

    public static function getWithdrawalCharge(int $amount): int
    {
        Log::info('...[SRV - PAYMENTS]: Get Withdrawal Charge...', [$amount]);

        return Cache::remember("withdrawal_charge_$amount", (24 * 60 * 60), function() use ($amount) {
            return parent::fetch(self::baseUrl()."/charges/withdrawal/$amount");
        });
    }
}
