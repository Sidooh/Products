<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoucherRequest;
use App\Models\Enterprise;
use App\Repositories\VoucherRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

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
        $data['destination'] = $destination ?? $account['phone'];

        $transaction = $this->createTransaction($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Voucher Request Successful');
    }

    /**
     * @throws Throwable
     */
    public function disburse(VoucherRequest $request): JsonResponse
    {
        $data = $request->all();
        $enterprise = Enterprise::find($data['enterprise_id']);

        if(!isset($data['amount'])) {
            $data['amount'] = match ($data['disburse_type']) {
                "LUNCH" => $enterprise->max_lunch,
                "GENERAL" => $enterprise->max_general
            };

            if(!isset($data['amount'])) {
                return $this->errorResponse("Amount is required! default amount for {$data['disburse_type']} voucher not set");
            }
        }

        if(!isset($data['accounts'])) {
            $data['accounts'] = $enterprise->enterpriseAccounts->pluck('account_id')->toArray();
        }

        VoucherRepository::disburse($enterprise, $data);

        $message = "{$data['disburse_type']} Voucher Disburse Request Successful";
        return $this->successResponse($data, $message);
    }
}
