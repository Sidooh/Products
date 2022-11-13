<?php

namespace App\Services;

use App\DTOs\PaymentDTO;
use App\Enums\Description;
use App\Enums\PaymentMethod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    public static function getAll(): array
    {
        Log::info('...[SRV - PAYMENTS]: Get All...');

        $url = self::baseUrl().'/payments';

        return Cache::remember('all_payments', (60 * 60 * 24), fn() => parent::fetch($url));
    }

    public static function baseUrl()
    {
        return config('services.sidooh.services.payments.url');
    }

    public static function requestPayment(PaymentDTO $paymentData): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Request Payment...');

        $endpoint = self::baseUrl().$paymentData->endpoint;

        return parent::fetch($endpoint, 'POST', (array) $paymentData);
    }

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

    public static function creditVoucher(int $accountId, $amount, Description $description, $notify = false): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Credit Voucher...');

        return parent::fetch(self::baseUrl().'/payments/voucher/credit', 'POST', [
            'account_id'  => $accountId,
            'amount'      => $amount,
            'description' => $description->value,
            'notify'      => $notify,
        ]);
    }

    public static function find(int $paymentId): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Find Payment...');

        return parent::fetch(self::baseUrl()."/payments/$paymentId");
    }

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
    public static function findVoucherByAccount(int $accountId): ?array
    {
        return parent::fetch(self::baseUrl()."/accounts/$accountId/vouchers");
    }

    // TODO: Add by voucher type filter
    public static function findSidoohVoucherIdForAccount(int $accountId): ?int
    {
        Log::info('...[SRV - PAYMENTS]: Find Sidooh Voucher...', [$accountId]);

        return Cache::remember($accountId.'_voucher', (60 * 60 * 24), function() use ($accountId) {
            return collect(parent::fetch(self::baseUrl()."/accounts/$accountId/vouchers"))
                ->first(fn($v) => $v['type'] === 'SIDOOH');
        })['id'];
    }
}
