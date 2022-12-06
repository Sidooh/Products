<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\EarningAccountType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\EarningRequest;
use App\Models\EarningAccount;
use App\Repositories\V2\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WithdrawController extends Controller
{
    /**
     * @throws Exception|\Throwable
     */
    public function __invoke(EarningRequest $request): JsonResponse
    {
        Log::info('...[CTRL - WITHDRAWv2]: Process Withdraw Request...', $request->all());

        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        // TODO: Shift to repository function?
        $earningAccounts = EarningAccount::select(['type', 'self_amount', 'invite_amount'])
            ->whereAccountId($account['id'])
            ->get();

        if ($earningAccounts->count() === 0) {
            return $this->errorResponse('No earnings found for user');
        }

        [$creditAccounts, $debitAccounts] = $earningAccounts
            ->partition(fn($a) => $a->type !== EarningAccountType::WITHDRAWALS->name);

        $totalEarned = $creditAccounts->reduce(fn($total, $account) => $total + $account->balance);
        $totalWithdrawn = $debitAccounts->reduce(fn($total, $account) => $total + $account->balance);

        // 20% for current account and 50 for charges
        if (.2 * ($totalEarned - $totalWithdrawn) - 50 < $data['amount']) {
            return $this->errorResponse('Earning balance is insufficient');
        }

        $transaction = [
            'initiator'   => $data['initiator'],
            'amount'      => $data['amount'],
            'destination' => $data['target_number'] ?? $account['phone'],
            'type'        => TransactionType::WITHDRAWAL,
            'description' => Description::EARNINGS_WITHDRAWAL,
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::WITHDRAWAL,
            'account'     => $account,
        ];

        $data = [
            'method' => $request->has('method') ? PaymentMethod::from($request->input('method')) : PaymentMethod::MPESA,
        ];

        $transaction = TransactionRepository::createWithdrawalTransaction($transaction, $data);

        return $this->successResponse($transaction->refresh(), 'Withdrawal Request Received');
    }
}
