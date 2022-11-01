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

class PaymentsController extends Controller
{
    use ApiResponse;

    /**
     * @throws Throwable
     */
    public function processCallback(Request $request)
    {
        Log::info('...[CTRL - PAYMENT]: Process Payment Callback...', $request->all());

        $transaction = Transaction::withWhereHas('payment', function($query) use ($request) {
            $query->wherePaymentId($request->id);
        })->whereStatus(Status::PENDING)->first();

        if ($request->status === Status::FAILED->value) {
            TransactionRepository::handleFailedPayment($transaction, $request);
        }

        if ($request->status === Status::COMPLETED->value) {
            TransactionRepository::handleCompletedPayment($transaction, $request);
        }

        return response()->json(['status' => true]);
    }
}
