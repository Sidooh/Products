<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
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
            "phone" => "phone:KE",
            "provider" => "string"
        ]);

        $payments = $request->collect("payments");

        [$completedPayments, $failedPayments] =
            $payments->partition(fn($p) => $p['status'] === Status::COMPLETED->value);

        Log::info("asd");
        Log::info($completedPayments);
        Log::info($failedPayments);
        Log::info($payments);

        if ($failedPayments) {
            Transaction::whereIn("id", $failedPayments->pluck('payable_id'))->update(["status" => Status::FAILED]);
        }

        if ($completedPayments) {
            // TODO: Will this work?
            $transactions = Transaction::findMany($completedPayments->pluck('payable_id'));

            TransactionRepository::requestPurchase($transactions, $request->all());
        }
    }
}
