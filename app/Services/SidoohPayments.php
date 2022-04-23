<?php

namespace App\Services;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    /**
     * @throws AuthenticationException
     */
    public static function pay(array $transactions, string $method, $totalAmount, array $data = []): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Make Payment...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . "/v1/payments";

        return parent::fetch($url, "POST", [
            "transactions" => $transactions,
            "method"       => $method,
            "total_amount" => $totalAmount,
            "data"         => $data,
        ]);
    }

    /**
     * @throws RequestException
     */
    public static function creditVoucher(int $accountId, $amount, $notify = false): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Credit Voucher...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . '/v1/payments/voucher/credit';

        return parent::fetch($url, "POST", [
            "account_id" => $accountId,
            "amount"     => $amount,
            "notify"     => $notify
        ]);
    }

    /**
     * @throws RequestException
     */
    public static function voucherDisbursement(int $enterpriseId, $data): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Voucher Disbursement...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . '/v1/payments/voucher/disburse';

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
        $url = config('services.sidooh.services.payments.url') . "/v1/payments/details/$transactionId/$accountId";

        return parent::fetch($url);
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function findVoucher(int $voucherId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/v1/payments/vouchers/$voucherId";

        return parent::fetch($url);
    }
}
