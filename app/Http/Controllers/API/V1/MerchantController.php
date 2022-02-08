<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantRequest;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param MerchantRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(MerchantRequest $request): JsonResponse
    {
        $data = $request->all();

        $data['account'] = SidoohAccounts::find($data['account_id']);
        $data['product'] = 'merchant';
        $data['type'] = TransactionType::PAYMENT;
        $data['description'] = "Merchant Payment";
        $data['method'] = "VOUCHER";

        $transaction = $this->createTransaction($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Merchant Request Successful!');
    }
}
