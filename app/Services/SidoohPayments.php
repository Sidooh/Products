<?php

namespace App\Services;

use App\Enums\Description;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    static function getAll(): array
    {
        Log::info('...[SRV - PAYMENTS]: Get All...');

        $url = config('services.sidooh.services.payments.url') . "/payments";

        return parent::fetch($url);
    }

    /**
     * @throws AuthenticationException
     */
    public static function pay(array $transactions, string $method, $totalAmount, array $data = []): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Make Payment...');

        $url = config('services.sidooh.services.payments.url') . "/payments";

        return parent::fetch($url, "POST", [
            "transactions" => $transactions,
            "method"       => $method,
            "total_amount" => $totalAmount,
            "data"         => $data,
        ]);
    }

    /**
     * @throws RequestException|AuthenticationException
     */
    public static function creditVoucher(int $accountId, $amount, Description $description, $notify = false): ?array
    {
        Log::info('...[SRV - PAYMENTS]: Credit Voucher...');

        $url = config('services.sidooh.services.payments.url') . '/payments/voucher/credit';

        return parent::fetch($url, "POST", [
            "account_id"  => $accountId,
            "amount"      => $amount,
            "description" => $description->value,
            "notify"      => $notify
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
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function findPaymentDetails(int $transactionId, int $accountId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/payments/details/$transactionId/$accountId";

        return parent::fetch($url);
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function findVoucher(int $voucherId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/payments/vouchers/$voucherId";

        return parent::fetch($url);
    }
}
