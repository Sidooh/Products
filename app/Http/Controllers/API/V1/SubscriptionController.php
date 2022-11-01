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

        $account = SidoohAccounts::find($data['account_id']);

        $transactions = [
            [
                'initiator' => $data['initiator'],
                'amount' => $data['amount'],
                'type' => TransactionType::PAYMENT,
                'description' => Description::SUBSCRIPTION_PURCHASE,
                'account_id' => $data['account_id'],
                'account' => SidoohAccounts::find($data['account_id']),
            ],
        ];

        $data = [
            'payment_account' => $account,
            'product' => 'subscription',
            'method' => $data['method'] ?? PaymentMethod::MPESA->value,
        ];

        $transactionIds = TransactionRepository::createTransaction($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Subscription Request Successful!');
    }
}
