<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\MerchantType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantRequest;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    public function __invoke(MerchantRequest $request): JsonResponse
    {
        Log::info('...[CTRL - MERCHANT]: Process Merchant Request...', $request->all());

        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        $transactionData = [
            'destination' => $data['business_number'],
            'initiator'   => $data['initiator'],
            'amount'      => $data['amount'],
            'type'        => TransactionType::PAYMENT,
            'description' => Description::MERCHANT_PAYMENT->value.(isset($data['account_number']) ? ' - '.$data['account_number'] : ''),
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::MERCHANT,
            'account'     => $account,
        ];
        $data = [
            'payment_account' => $account,
            'method'          => $request->has('method') ? PaymentMethod::from($request->input('method')) : PaymentMethod::MPESA,
            'merchant_type'   => MerchantType::from($request->merchant_type),
            'business_number' => $request->business_number,
            'account_number'  => $request->account_number,
        ];

        $transactionId = TransactionRepository::createB2bTransaction($transactionData, $data);

        return $this->successResponse(['transactions' => [$transactionId]], 'Merchant Request Successful!');
    }
}
