<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    /**
     * @throws Exception
     */
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $data = $request->all();
        $data += [
            "account"     => SidoohAccounts::find($data['account_id']),
            "product"     => "subscription",
            "method"      => $data['method'] ?? PaymentMethod::MPESA->value,
            "type"        => TransactionType::PAYMENT,
            "description" => Description::SUBSCRIPTION_PURCHASE
        ];

        $transaction = TransactionRepository::createTransaction($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Subscription Request Successful');
    }
}
