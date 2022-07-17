<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
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

        $account = SidoohAccounts::find($data['account_id']);

        $transactions = [
            [
                "destination" => $data['target_number'] ?? $account["phone"],
                "initiator" => $data["initiator"],
                "amount" => $data["amount"],
                "type" => TransactionType::PAYMENT,
                //TODO: Use Description::UTILITY_PURCHASE - $data['provider'] format
                "description" => Description::UTILITY_PURCHASE->value . ' - ' . $data['provider'],
                "account_id" => $data['account_id'],
                "product_id" => ProductType::UTILITY,
                "account" => $account,
            ]
        ];

        $data = [
            "payment_account" => $account,
            "provider"        => $data['provider'],
            "method"          => $data['method'] ?? PaymentMethod::MPESA->value,
        ];

        if($request->input("initiator") === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transactionIds = TransactionRepository::createTransactions($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Utility Request Successful!');
    }
}
