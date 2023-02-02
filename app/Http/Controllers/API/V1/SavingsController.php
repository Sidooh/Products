<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SavingsController extends Controller
{
    use ApiResponse;

    /**
     * @throws Throwable
     */
    public function processCallback(Request $request): JsonResponse
    {
        Log::info('...[CTRL - SAVINGS]: Process Savings Callback...', $request->all());

        $transaction = Transaction::withWhereHas('savingsTransaction', function($query) use ($request) {
            $query->whereSavingsId($request->id);
        })->whereStatus(Status::PENDING)->first();

        if (! $transaction) {
            Log::critical('Error processing savings callback - no transaction');

            return response()->json(['status' => true]);
        }

        if ($request->status === Status::FAILED->value) {
            TransactionRepository::handleFailedWithdrawal($transaction, $request);
        }

        if ($request->status === Status::COMPLETED->value) {
            TransactionRepository::handleCompletedWithdrawal($transaction);
        }

        return response()->json(['status' => true]);
    }
}
