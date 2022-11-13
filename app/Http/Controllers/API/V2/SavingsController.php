<?php

namespace App\Http\Controllers\API\V2;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\V2\TransactionRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SavingsController extends Controller
{
    use ApiResponse;

    /**
     * @throws Throwable
     */
    public function processCallback(Request $request)
    {
        Log::info('...[CTRL - SAVINGS]: Process Savings Callback...', $request->all());

        $transaction = Transaction::withWhereHas('savingsTransaction', function($query) use ($request) {
            $query->whereSavingsId($request->id);
        })->whereStatus(Status::PENDING)->first();

        if ($request->status === Status::FAILED->value) {
            TransactionRepository::handleFailedWithdrawal($transaction, $request);
        }

        if ($request->status === Status::COMPLETED->value) {
            TransactionRepository::handleCompletedWithdrawal($transaction);
        }

        return response()->json(['status' => true]);
    }
}
