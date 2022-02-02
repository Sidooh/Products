<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AirtimeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $data = $request->all();
        $account = SidoohAccounts::find($data['account_id']);

        $data['account'] = SidoohAccounts::find($data['account_id']);
        $data['product'] = 'airtime';
        $data['type'] = TransactionType::PAYMENT;
        $data['description'] = "Airtime Purchase";

        $transaction = $this->init($data, $account);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Airtime Request Successful');
    }

    /**
     * @throws Exception
     */
    public function init($data, $account): Transaction
    {
        $data['destination'] = $data['target_number'] ?? $account['phone'];

        return $this->createTransaction($data);
    }
}
