<?php

namespace App\Services;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    /**
     * @throws AuthenticationException
     */
    static function getAll(): array
    {
        Log::info('...[SRV - PAYMENTS]: Get All...');

        $url = config('services.sidooh.services.payments.url') . "/payments";

        $pays = Cache::remember('all_payments', (60 * 60 * 24), fn() => parent::fetch($url));

        return $pays;
    }

    /**
     * @throws AuthenticationException
     */
    public static function requestPayment(Collection $transactions, PaymentMethod $method, string $debit_account): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Request Payment...');

        $url = config('services.sidooh.services.payments.url') . "/payments";

        return parent::fetch($url, "POST", [
            "transactions" => $transactions->toArray(),
            "payment_mode" => $method->name,
            "debit_account" => $debit_account
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public static function creditVoucher(int $accountId, $amount, Description $description, $notify = false): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Credit Voucher...');

        $url = config('services.sidooh.services.payments.url') . '/payments/voucher/credit';

        return parent::fetch($url, "POST", [
            "account_id" => $accountId,
            "amount" => $amount,
            "description" => $description->value,
            "notify" => $notify
        ]);
    }

    /**
     * @throws RequestException|AuthenticationException
     */
    public static function voucherDisbursement(int $enterpriseId, $data): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Voucher Disbursement...');

        $url = config('services.sidooh.services.payments.url') . '/payments/voucher/disburse';

        return parent::fetch($url, "POST", [
            "enterprise_id" => $enterpriseId,
            "data"          => $data
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    public static function find(int $paymentId): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Find Payment...');

        $url = config('services.sidooh.services.payments.url') . "/payments/$paymentId";

        return parent::fetch($url);
    }

    /**
     * @throws AuthenticationException
     */
    public static function findVoucher(int $voucherId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/payments/vouchers/$voucherId";

        return parent::fetch($url);
    }

    // TODO: Add by voucher type filter
    public static function findVoucherByAccount(int $accountId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/accounts/$accountId/vouchers";

        return parent::fetch($url);
    }
}
