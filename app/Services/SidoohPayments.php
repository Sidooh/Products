<?php

namespace App\Services;

use App\Enums\Description;
use App\Enums\PaymentMethod;
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

        return Cache::remember('all_payments', (60 * 60 * 24), fn() => parent::fetch($url));
    }

    public static function requestPayment(Collection $transactions, PaymentMethod $method, string $debit_account): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Request Payment...');

        return parent::fetch(self::baseUrl().'/payments', 'POST', [
            'transactions'  => $transactions->toArray(),
            'payment_mode'  => $method->name,
            'debit_account' => $debit_account,
        ]);
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

    // TODO: Add by voucher type filter
    public static function findVoucherByAccount(int $accountId): ?array
    {
        return parent::fetch(self::baseUrl()."/accounts/$accountId/vouchers");
    }
}
