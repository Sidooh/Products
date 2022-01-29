<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Payment;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductController extends Controller
{
    use ApiResponse;

    private array $data;
    private ProductRepository $repo;

    /**
     * Handle the incoming request.
     *
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws Exception|Throwable
     */
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $this->repo = new ProductRepository();
        $this->data = $request->all();

        $paymentMethod = PaymentMethod::tryFrom($this->data['method']);

        if(!$paymentMethod) throw new Exception('Invalid payment method!');

        $this->repo->setPaymentMethod($paymentMethod);

        $response = match ($this->data['product']) {
            'airtime' => $this->airtimePurchase(),
            'utility' => $this->utilityPurchase(),
            'subscription' => $this->subscriptionPurchase(),
            'voucher' => $this->voucherTransaction()
        };

        return $this->successResponse($response, 'Request successful');
    }

    /**
     * @throws Exception
     * @throws Throwable
     */
    public function airtimePurchase(): Payment
    {
        $this->data['type'] = TransactionType::PAYMENT;
        $this->data['description'] = "Airtime Purchase";

        $this->repo->createTransaction($this->data)->getTransaction();
        $this->repo->initiatePayment($this->data['phone'])->createPayment();
        $this->repo->requestPurchase($this->data['product']);

        return $this->repo->getPayment();
    }

    public function utilityPurchase()
    {
        return $this->data['product'];
    }

    /**
     * @throws Exception|Throwable
     */
    public function subscriptionPurchase()
    {
        $this->data['type'] = TransactionType::PAYMENT;
        $this->data['description'] = "Subscription Purchase";

        $this->repo->createTransaction($this->data)->getTransaction();
        $this->repo->initiatePayment($this->data['phone'])->createPayment();
        $this->repo->requestPurchase($this->data['product']);

        return $this->data['product'];
    }

    public function voucherTransaction()
    {
        return $this->data['product'];
    }
}
