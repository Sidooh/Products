<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AirtimeRequest;
use App\Models\AirtimeAccount;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AirtimeController extends Controller
{
    /**
     * @throws \Throwable
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function __invoke(AirtimeRequest $request): JsonResponse
    {
        Log::info('...[CTRL - AIRTIME]: Process Airtime Request...', $request->all());

        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        $transactionData = [
            'destination' => $data['target_number'] ?? $account['phone'],
            'initiator'   => $data['initiator'],
            'amount'      => $data['amount'],
            'type'        => TransactionType::PAYMENT,
            'description' => Description::AIRTIME_PURCHASE,
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::AIRTIME,
            'account'     => $account,
        ];

        $data = [
            'method' => $request->has('method')
                ? PaymentMethod::from($request->input('method'))
                : PaymentMethod::MPESA,
        ];

        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transaction = TransactionRepository::createTransaction($transactionData, $data);

        return $this->successResponse($transaction, 'Airtime Request Successful!');
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function accounts(Request $request): JsonResponse
    {
        $request->validate([
            'page'      => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|digits_between:10,1000',
        ]);

        $relations = $request->string('with')->explode(',');
        $perPage = $request->integer('page_size', 100);
        $page = $request->integer('page', 1);

        $accounts = AirtimeAccount::select(['id', 'provider', 'priority', 'account_id', 'account_number', 'created_at'])
            ->latest()->limit($perPage)->offset($perPage * ($page - 1))->get();

        if ($relations->contains('account')) {
            $accounts = withRelation('account', $accounts, 'account_id', 'id');
        }

        return $this->successResponse(paginate($accounts, AirtimeAccount::count(), $perPage, $page));
    }
}
