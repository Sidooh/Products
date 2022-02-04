<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoucherRequest;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class VoucherController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function topUp(VoucherRequest $request): JsonResponse
    {
        $data = $request->all();
        $account = SidoohAccounts::find($data['account_id']);

        $data['account'] = SidoohAccounts::find($data['account_id']);
        $data['product'] = 'voucher';
        $data['method'] = 'MPESA';
        $data['type'] = TransactionType::PAYMENT;
        $data['description'] = "Voucher Purchase";

        $transaction = $this->init($data, $account);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Voucher Request Successful');
    }

    public function disburse(VoucherRequest $request): JsonResponse
    {
        return $this->successResponse(['']);
    }

    /**
     * @throws Exception
     */
    public function init($data, $account): Transaction
    {
        $data['destination'] = $destination ?? $account['phone'];

        return $this->createTransaction($data);
    }
}
