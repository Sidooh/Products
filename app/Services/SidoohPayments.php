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

        $url = config('services.sidooh.services.payments.url');

        return self::send()->post($url, [
            "transactions" => $transactions,
            "method"       => $method,
            "total_amount" => $totalAmount,
            "data"         => $data,
        ])->throw();
    }

    /**
     * @throws RequestException
     */
    public static function voucherDeposit(int $accountId, $amount): PromiseInterface|Response
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Voucher Deposit...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . '/voucher/deposit';

        return self::send()->post($url, [
            "account_id" => $accountId,
            "amount"     => $amount
        ])->throw();
    }

    /**
     * @throws RequestException
     */
    public static function voucherDisbursement(int $enterpriseId, $data): PromiseInterface|Response
    {
        Log::info('--- --- --- --- ---   ...[SRV - PAYMENTS]: Voucher Disbursement...   --- --- --- --- ---');

        $url = config('services.sidooh.services.payments.url') . '/voucher/disburse';

        return self::send()->post($url, [
            "enterprise_id" => $enterpriseId,
            "data"          => $data
        ])->throw();
    }
}
