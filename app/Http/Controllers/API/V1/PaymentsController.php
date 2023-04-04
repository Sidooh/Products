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

class PaymentsController extends Controller
{
    use ApiResponse;

    /**
     * @throws Throwable
     */
    public function processCallback(Request $request): JsonResponse
    {
        Log::info('...[CTRL - PAYMENT]: Process Payment Callback...', $request->all());

        $transaction = Transaction::withWhereHas('payment', function($query) use ($request) {
            $query->wherePaymentId($request->id);
        })->whereStatus(Status::PENDING)->first();

        if (! $transaction) {
            Log::critical('Error processing payment callback - no transaction');

            return response()->json(['status' => true]);
        }

        dispatch(function() use ($transaction, $request) {
            if ($request->status === Status::FAILED->value) {
                TransactionRepository::handleFailedPayment($transaction, $request);
            }

            if ($request->status === Status::COMPLETED->value) {
                if ($request->has('mpesa_code')) {
                    $transaction->payment->update([
                        'extra' => [
                            ...$transaction->payment->extra,
                            'mpesa_code' => $request->string('mpesa_code'),
                            'mpesa_merchant' => $request->string('mpesa_merchant'),
                        ],
                    ]);
                }

                TransactionRepository::handleCompletedPayment($transaction);
            }
        })->afterResponse();

        return response()->json(['status' => true]);
    }
}
