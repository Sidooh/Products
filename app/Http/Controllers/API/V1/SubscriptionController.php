<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Transaction;
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

        $data['account'] = SidoohAccounts::find($data['account_id']);
        $data['product'] = 'subscription';
        $data['type'] = TransactionType::PAYMENT;
        $data['description'] = "Subscription Purchase";

        $transaction = $this->init($data);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Subscription Request Successful');
    }

    /**
     * @throws Exception
     */
    public function init($data): Transaction
    {
        return $this->createTransaction($data);
    }
}
