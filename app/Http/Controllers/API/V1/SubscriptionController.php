<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Repositories\SubscriptionRepository;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * @throws Exception|\Throwable
     */
    public function __invoke(SubscriptionRequest $request): JsonResponse
    {
        Log::info('...[CTRL - SUBSCRIPTION]: Process Subscription Request...', $request->all());

        $data = $request->all();

        $account = SidoohAccounts::find($data['account_id']);

        $subscriptionType = SubscriptionType::find($data['subscription_type_id']);

        // Check Subscription doesn't exist
        $subscription = Subscription::whereAccountId($account['id'])->latest()->first();
        if ($subscription && $subscription->status === Status::ACTIVE->name) {
            return $this->errorResponse('Account has an existing active subscription', 400);
        }

        $transactions = [
            [
                'initiator'   => $data['initiator'],
                'amount'      => $subscriptionType->price,
                'destination' => $data['target_number'] ?? $account['phone'],
                'type'        => TransactionType::PAYMENT,
                'description' => Description::SUBSCRIPTION_PURCHASE,
                'account_id'  => $data['account_id'],
                'product_id'  => ProductType::SUBSCRIPTION,
                'account'     => $account,
            ],
        ];

        $data = [
            'payment_account' => $account,
            'method'          => $request->has('method') ? PaymentMethod::from($request->input('method'))
                : PaymentMethod::MPESA,
        ];

        // TODO: Also ensure we can't use other voucher here
        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transactionIds = TransactionRepository::createTransactions($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Subscription Request Successful!');
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        $subscriptions = Subscription::select([
            'id',
            'start_date',
            'end_date',
            'status',
            'account_id',
            'subscription_type_id',
            'created_at',
        ])->latest()->with('subscriptionType:id,title,price,duration,active,period')->get();

        if (in_array('account', $relations)) {
            $subscriptions = withRelation('account', $subscriptions, 'account_id', 'id');
        }

        return $this->successResponse($subscriptions);
    }

    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if (in_array('subscription_type', $relations)) {
            $subscription->load('subscriptionType');
        }

        return $this->successResponse($subscription);
    }

    public function getSubTypes(): JsonResponse
    {
        $subTypes = SubscriptionType::select([
            'id',
            'title',
            'price',
            'level_limit',
            'duration',
            'active',
            'period',
        ])->latest()->get();

        return $this->successResponse($subTypes);
    }

    public function checkExpiry(): JsonResponse
    {
        $response = SubscriptionRepository::checkExpiry();

        return $this->successResponse($response);
    }
}
