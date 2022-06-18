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
        Log::info('--- --- ---   ...[CONTROLLER - PAYMENT]: Request Purchase...   --- --- ---', $request->all());

        $request->validate([
            "payments" => "required|array",
            "phone"    => "phone:KE",
            "provider" => "string"
        ]);

        $payments = $request->collect("payments");

        $completedPaymentsIds = $payments->where("status", Status::COMPLETED->value)->pluck("payable_id");
        $failedPaymentIds = $payments->where("status", Status::FAILED->value)->pluck("payable_id");

        if($failedPaymentIds->isNotEmpty()) {
            Transaction::whereIn("id", $failedPaymentIds)->update(["status" => Status::FAILED]);
        }

        if($completedPaymentsIds->isNotEmpty()) {
            // TODO: Will this work?
            $transactions = Transaction::findMany($completedPaymentsIds);

            TransactionRepository::requestPurchase($transactions, $request->all());
        }
    }
}
