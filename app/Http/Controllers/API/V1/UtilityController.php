<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;

class UtilityController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $data = $request->all();

        $data['account'] = SidoohAccounts::find($data['account_id']);
        $data['product'] = 'utility';
        $data['type'] = TransactionType::PAYMENT;
        $data['description'] = "{$data['provider']} Payment";
        $data['destination'] = $data['account_number'];

        $transaction = $this->init($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Utility Request Successful');
    }

    /**
     * @throws Exception
     */
    public function init($data): Transaction
    {
        return $this->createTransaction($data);
    }
}
