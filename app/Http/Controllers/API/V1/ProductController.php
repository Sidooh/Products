<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    private array $data;

    /**
     * Handle the incoming request.
     *
     * @param ProductRequest $request
     * @return JsonResponse
     */
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $this->data = $request->all();

        $response = match($this->data['product']) {
            'airtime' => $this->airtimePurchase(),
            'utility' => $this->utilityPurchase(),
            'subscription' => $this->subscriptionPurchase(),
            'voucher' => $this->voucherTransaction()
        };

        return $this->successResponse($response, 'Request successful');
    }

    public function airtimePurchase()
    {
        return $this->data['product'];
    }

    public function utilityPurchase()
    {
        return $this->data['product'];
    }

    public function subscriptionPurchase()
    {
        return $this->data['product'];
    }

    public function voucherTransaction()
    {
        return $this->data['product'];
    }
}
