<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FloatRequest;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;

class FloatController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param FloatRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function topUp(FloatRequest $request): JsonResponse
    {
        $data = $request->all();

        if($data['initiator'] === 'AGENT') {
            $data['account'] = SidoohAccounts::find($data['account_id']);
        }

        $data['product'] = 'float';
        $data['method'] = 'MPESA';
        $data['type'] = TransactionType::PAYMENT;
        $data['description'] = "Float Purchase";

        $transaction = TransactionRepository::createTransactions($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Voucher Request Successful');
    }
}
