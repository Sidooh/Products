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

        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        $subscriptionType = SubscriptionType::find($data['subscription_type_id']);

        // Check Subscription doesn't exist
        $subscription = Subscription::whereAccountId($account['id'])->latest()->first();
        if ($subscription && $subscription->status === Status::ACTIVE->name) {
            return $this->errorResponse('Account has an existing active subscription', 400);
        }

        $transaction = [
            'initiator'   => $data['initiator'],
            'amount'      => $subscriptionType->price,
            'destination' => $data['target_number'] ?? $account['phone'],
            'type'        => TransactionType::PAYMENT,
            'description' => Description::SUBSCRIPTION_PURCHASE,
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::SUBSCRIPTION,
            'account'     => $account,
        ];

        $data = [
            'method' => $request->has('method') ? PaymentMethod::from($request->input('method'))
                : PaymentMethod::MPESA,
        ];

        // TODO: Also ensure we can't use other voucher here
        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transaction = TransactionRepository::createTransaction($transaction, $data);

        return $this->successResponse($transaction, 'Subscription Request Successful!');
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page'      => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|between:10,1000',
        ]);

        $perPage = $request->integer('page_size', 100);
        $page = $request->integer('page', 1);

        $subscriptions = Subscription::select([
            'id',
            'start_date',
            'end_date',
            'status',
            'account_id',
            'subscription_type_id',
            'created_at',
        ])->latest()->with('subscriptionType:id,title,price,duration,active,period')->limit(1000)->get();

        if ($request->string('with')->contains('account')) {
            $subscriptions = withRelation('account', $subscriptions, 'account_id', 'id');
        }

        return $this->successResponse(paginate($subscriptions, Subscription::count(), $perPage, $page));
    }

    public function show(Request $request, Subscription $subscription): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if (in_array('subscription_type', $relations)) {
            $subscription->load('subscriptionType');
        }

        if (in_array('account', $relations)) {
            $subscription->account = SidoohAccounts::find($subscription->account_id);
        }

        return $this->successResponse($subscription);
    }

    public function checkExpiry(): JsonResponse
    {
        $response = SubscriptionRepository::checkExpiry();

        return $this->successResponse($response);
    }
}
