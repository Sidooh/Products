<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class SidoohPayments extends SidoohService
{
    /**
     * @throws RequestException
     */
    public static function pay(array $transactions, string $method, $totalAmount, array $data = []): PromiseInterface|Response
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Make Payment...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . "/v1/payments";

        return parent::http()->post($url, [
            "transactions" => $transactions,
            "method"       => $method,
            "total_amount" => $totalAmount,
            "data"         => $data,
        ])->throw();
    }

    /**
     * @throws RequestException
     */
    public static function creditVoucher(int $accountId, $amount, $notify = false): PromiseInterface|Response
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Credit Voucher...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . '/v1/payments/voucher/credit';

        return parent::http()->post($url, [
            "account_id" => $accountId,
            "amount"     => $amount,
            "notify"     => $notify
        ])->throw();
    }

    /**
     * @throws RequestException
     */
    public static function voucherDisbursement(int $enterpriseId, $data): PromiseInterface|Response
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Voucher Disbursement...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . '/v1/payments/voucher/disburse';

        return parent::http()->post($url, [
            "enterprise_id" => $enterpriseId,
            "data"          => $data
        ])->throw();
    }

    public static function findPaymentDetails(int $transactionId, int $accountId)
    {
        $url = config('services.sidooh.services.payments.url') . "/v1/payments/details/$transactionId/$accountId";

        return parent::http()->get($url)->json();
    }
}
