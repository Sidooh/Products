<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoucherRequest;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class VoucherController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  VoucherRequest  $request
     * @return JsonResponse
     *
     * @throws Exception|Throwable
     */
    public function topUp(VoucherRequest $request): JsonResponse
    {
        Log::info('...[CTRL - VOUCHER]: Process Voucher Request...', $request->all());

        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        // == and not ===  since they are int and string sometimes
        if (isset($data['target_number'])) {
            if (substr($account['phone'], -9) === substr($data['target_number'], -9)) {
                return $this->errorResponse('Target number cannot be your account phone number', 422);
            }
        }

        $transactionsData = [
            [
                'destination' => $data['target_number'] ?? $account['phone'],
                'initiator'   => $data['initiator'],
                'amount'      => $data['amount'],
                'type'        => TransactionType::PAYMENT,
                'description' => Description::VOUCHER_PURCHASE,
                'account_id'  => $data['account_id'],
                'product_id'  => ProductType::VOUCHER,
                'account'     => $account,
            ],
        ];

        $data = [
            'payment_account' => $account,
            'method'          => $request->has('method') ? PaymentMethod::from($request->input('method'))
                : PaymentMethod::MPESA,
        ];

        if ($request->has('debit_account') && $data['method'] === PaymentMethod::MPESA) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transactionIds = TransactionRepository::createTransactions($transactionsData, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Voucher Request Successful!');
    }
}
