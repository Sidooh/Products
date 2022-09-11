<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\FloatRequest;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FloatController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param FloatRequest $request
     * @throws Exception*@throws \Throwable
     * @throws \Throwable
     * @return JsonResponse
     */
    public function topUp(FloatRequest $request): JsonResponse
    {
        $data = $request->validated();

        Log::info('...[CTRL - FLOAT]: Process Float Request...', $data);

        if($data['initiator'] === Initiator::AGENT->value) {
            $account = SidoohAccounts::find($data['account_id']);
        }

        $transactionsData = [
            [
                "destination" => $data['target_number'] ?? $account["phone"],
                "initiator"   => $data["initiator"],
                "amount"      => $data["amount"],
                "type"        => TransactionType::PAYMENT,
//                "description" => Description::FLOAT_PURCHASE,
                "account_id"  => $data['account_id'],
                "product_id"  => ProductType::FLOAT,
                "account"     => $account,
            ]
        ];

        $data = [
            "payment_account" => $account,
            "method"          => PaymentMethod::MPESA,
        ];

        $transactionIds = TransactionRepository::createTransactions($transactionsData, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Float Request Successful!');
    }
}
