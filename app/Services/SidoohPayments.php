<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SidoohPayments
{
    /**
     * @throws RequestException
     */
    public static function pay(int $transactionId, PaymentMethod $method, $amount, array $data = []): PromiseInterface|Response
    {
        Log::alert('****************************    SIDOOH-SRV PAYMENTS: Product Payment     ****************************');

        $url = config('services.sidooh.services.payments.url');

        return Http::retry(3)->post($url, [
            "transaction_id" => $transactionId,
            "method"         => $method,
            "amount"         => $amount,
            "data"           => $data,
        ])->throw();
    }

    /**
     * @throws RequestException
     */
    public static function voucherDeposit(int $accountId, $amount): PromiseInterface|Response
    {
        Log::alert('****************************    SIDOOH-SRV PAYMENTS: Voucher Deposit     ****************************');

        $url = config('services.sidooh.services.payments.url') . '/voucher/deposit';

        return Http::retry(3)->post($url, [
            "account_id" => $accountId,
            "amount"     => $amount
        ])->throw();
    }

    /**
     * @throws RequestException
     */
    public static function voucherDisbursement(int $enterpriseId, $data): PromiseInterface|Response
    {
        Log::alert('****************************    SIDOOH-SRV PAYMENTS: Voucher Disbursement     ****************************');

        $url = config('services.sidooh.services.payments.url') . '/voucher/disburse';

        return Http::retry(3)->post($url, [
            "enterprise_id" => $enterpriseId,
            "data"          => $data
        ])->throw();
    }
}
