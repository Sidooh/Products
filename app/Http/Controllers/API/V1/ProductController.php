<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use JetBrains\PhpStorm\Pure;

class ProductController extends Controller
{
    use ApiResponse;

    private array $data;

    /**
     * @param ProductRepository $repo
     */
    #[Pure]
    public function __construct(private ProductRepository $repo = new ProductRepository()) {}

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
        $this->data['type'] = TransactionType::PAYMENT;
        $this->data['description'] = "Airtime Purchase";
        $transaction = $this->repo->createTransaction($this->data);
        $provider = $this->repo->initiatePayment($transaction, $this->data);

        return $transaction->getPayment();
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
