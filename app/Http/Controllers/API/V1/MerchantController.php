<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\MerchantType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\MerchantRequest;
use App\Repositories\MerchantRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MerchantController extends Controller
{
    public function __construct(private $repo = new MerchantRepository)
    {
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \Throwable
     */
    public function __invoke(MerchantRequest $request): JsonResponse
    {
        Log::info('...[CTRL - MERCHANT]: Process Merchant Request...', $request->all());

        $data = [
            'method'          => $request->has('method') ? $request->enum('method', PaymentMethod::class) : PaymentMethod::MPESA,
            'merchant_type'   => $request->enum('merchant_type', MerchantType::class),
            'business_number' => $request->integer('business_number'),
            'account_number'  => $request->string('account_number'),
        ];

        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transaction = $this->repo->transact($request->validated(), $data);

        return $this->successResponse($transaction, 'Merchant Request Successful!');
    }
}
