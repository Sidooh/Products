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
use Illuminate\Support\Facades\Log;

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
        Log::info('...[CTRL - UTILITY]: Process Utility Request...', $request->all());

        $data = $request->all();

        $account = SidoohAccounts::find($data['account_id']);

        $transactions = [
            [
                "destination" => $data["account_number"],
                "initiator" => $data["initiator"],
                "amount" => $data["amount"],
                "type" => TransactionType::PAYMENT,
                "description" => Description::UTILITY_PURCHASE->value . ' - ' . $data['provider'],
                "account_id" => $data['account_id'],
                "product_id" => ProductType::UTILITY,
                "account" => $account,
            ]
        ];

        $data = [
            "payment_account" => $account,
            "provider" => $data['provider'],
            "method" => $request->has("method") ? PaymentMethod::from($request->input("method")) : PaymentMethod::MPESA,
        ];

        if ($request->has("debit_account")) $data["debit_account"] = $request->input("debit_account");
//        if ($request->input("initiator") === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transactionIds = TransactionRepository::createTransactions($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Utility Request Successful!');
    }
}
