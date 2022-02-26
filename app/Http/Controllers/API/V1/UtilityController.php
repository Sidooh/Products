<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Repositories\TransactionRepository;
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

        $data += [
            'account' =>SidoohAccounts::find($data['account_id']),
            'product' => 'utility',
            'type' => TransactionType::PAYMENT,
            'description' => "{$data['provider']} Payment",
            'destination' =>$data['account_number'],
            "method" => $data['method'] ?? PaymentMethod::MPESA->value
        ];

        if($data['initiator'] === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transaction = TransactionRepository::createTransaction($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Utility Request Successful!');
    }
}
