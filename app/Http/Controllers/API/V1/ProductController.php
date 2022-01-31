<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\PaymentMethod;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Payment;
use App\Repositories\ProductRepository;
use App\Services\SidoohAccounts;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class ProductController extends Controller
{
    use ApiResponse;

    private array $data, $account;
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
        $this->data = $request->all();
        $this->repo = new ProductRepository();
        $this->account = SidoohAccounts::find($this->data['account_id']);

        $paymentMethod = PaymentMethod::tryFrom($this->data['method']);

        if(!$paymentMethod) throw new Exception('Invalid payment method!');

        $this->repo->setPaymentMethod($paymentMethod);
        $this->repo->setAccount($this->account);

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

        $this->repo->createTransaction($this->data);

        $targetNumber = $this->data['target_number'] ?? null;
        $mpesaNumber = $this->data['mpesa_number'] ?? null;

        $this->repo->initiatePayment($this->account['phone'], $targetNumber, $mpesaNumber)->createPayment();
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
