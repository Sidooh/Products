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
        Log::info('...[CONTROLLER - PAYMENT]: Request Purchase...', $request->all());

        $request->validate([
            "payments" => "required|array",
            "phone"    => "phone:KE",
            "provider" => "string"
        ]);

        $payments = $request->collect("payments");

        [
            $completedPayments,
            $failedPayments
        ] = $payments->partition(fn($p) => $p['status'] === Status::COMPLETED->value);

        if($failedPayments) {
            $payments = Payment::whereIn("payment_id", $failedPayments->pluck("id"));
            $payments->update(["status" => Status::FAILED]);

            Transaction::whereIn("id", $payments->pluck("transaction_id"))->update(["status" => Status::FAILED]);
        }

        if($completedPayments) {
            // TODO: Will this work?
            $payments = Payment::whereIn("payment_id", $completedPayments->pluck("id"));
            $payments->update(["status" => Status::COMPLETED]);

            $transactions = Transaction::findMany($payments->pluck("transaction_id"));

            TransactionRepository::requestPurchase($transactions, $request->all());
        }
    }
}
