<?php

namespace App\Services;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    /**
     * @throws RequestException
     */
    public static function pay(array $transactions, string $method, $totalAmount, array $data = []): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Make Payment...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . "/v1/payments";

        return self::fetch($url, "POST", [
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

        return self::fetch($url, "POST", [
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

        return self::fetch($url, "POST", [
            "enterprise_id" => $enterpriseId,
            "data"          => $data
        ]);
    }

    public static function findPaymentDetails(int $transactionId, int $accountId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/v1/payments/details/$transactionId/$accountId";

        return self::fetch($url);
    }

    public static function findVoucher(int $voucherId): ?array
    {
        $url = config('services.sidooh.services.payments.url') . "/v1/payments/vouchers/$voucherId";

        return self::fetch($url);
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    static function fetch(string $url, string $method = "GET", array $data = []): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Fetch...   --- --- --- --- ---', [
            "method" => $method,
            "data"   => $data
        ]);

        try {
            return parent::http()->send($method, $url, ['json' => $data])->throw()->json();
        } catch (RequestException|Exception $err) {
            if($err->getCode() === 401) throw new AuthenticationException();
        }
    }
}
