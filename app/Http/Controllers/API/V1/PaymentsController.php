<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
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

        $request->validate([
            "payments" => "required|array",
            "vouchers" => "array",
        ]);

        $payments = $request->collect("payments");

        [
            $completedPayments,
            $failedPayments
        ] = $payments->partition(fn($p) => $p['status'] === Status::COMPLETED->value);

        if (count($failedPayments)) {
            $transactions = Transaction::withWhereHas('payment', function ($query) use ($failedPayments) {
                $query->whereIn("payment_id", $failedPayments->pluck("id"));
            })->get();

            TransactionRepository::handleFailedTransactionPayments($transactions, $failedPayments);
        }

        if (count($completedPayments)) {
            // TODO: Will this work? Update to be similar to failed payments
            $payments = Payment::whereIn("payment_id", $completedPayments->pluck("id"));
            $payments->update(["status" => Status::COMPLETED]);

            $transactions = Transaction::find($payments->pluck("transaction_id"));

            TransactionRepository::requestPurchase($transactions, $request->all());
        }
    }
}
