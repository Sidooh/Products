<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\UtilityAccount;
use App\Repositories\V2\TransactionRepository;
use App\Services\SidoohAccounts;
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
            'description' => Description::UTILITY_PURCHASE->value . ' - ' . $data['provider'],
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::UTILITY,
            'account'     => $account,
        ];

        $data = [
            'provider'        => $data['provider'],
            'method'          => $request->has('method') ? PaymentMethod::from($request->input('method')) : PaymentMethod::MPESA,
        ];

        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }

        $transaction = TransactionRepository::createTransaction($transaction, $data);

        return $this->successResponse($transaction, 'Utility Request Successful!');
    }

    public function accounts(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));
        $accounts = UtilityAccount::select(['id', 'provider', 'priority', 'account_id', 'account_number', 'created_at'])
            ->latest()->get();

        if (in_array('account', $relations)) {
            $accounts = withRelation('account', $accounts, 'account_id', 'id');
        }

        return $this->successResponse($accounts);
    }
}
