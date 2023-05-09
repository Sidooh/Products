<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\UtilityAccount;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UtilityController extends Controller
{
    public function __invoke(ProductRequest $request): JsonResponse
    {
        Log::info('...[CTRL - UTILITY]: Process Utility Request...', $request->all());

        $data = $request->all();

        $account = SidoohAccounts::find($data['account_id']);

        $transaction = [
            'destination' => $data['account_number'],
            'initiator'   => $data['initiator'],
            'amount'      => $data['amount'],
            'type'        => TransactionType::PAYMENT,
            'description' => Description::UTILITY_PURCHASE->value.' - '.$data['provider'],
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::UTILITY,
            'account'     => $account,
        ];

        $data = [
            'provider' => $data['provider'],
            'method'   => $request->has('method') ? PaymentMethod::from($request->input('method')) : PaymentMethod::MPESA,
        ];

        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transaction = TransactionRepository::createTransaction($transaction, $data);

        return $this->successResponse($transaction, 'Utility Request Successful!');
    }

    /**
     * @throws AuthenticationException
     */
    public function accounts(Request $request): JsonResponse
    {
        $request->validate([
            'page'      => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|between:10,1000',
        ]);

        $perPage = $request->integer('page_size', 100);
        $page = $request->integer('page', 1);

        $accounts = UtilityAccount::select(['id', 'provider', 'priority', 'account_id', 'account_number', 'created_at'])
            ->latest()->get();

        if ($request->string('with')->contains('account')) {
            $accounts = withRelation('account', $accounts, 'account_id', 'id');
        }

        return $this->successResponse(paginate($accounts, UtilityAccount::count(), $perPage, $page));
    }
}
