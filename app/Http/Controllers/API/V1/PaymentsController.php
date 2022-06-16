<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentsController extends Controller
{
    use ApiResponse;

    /**
     * @throws Throwable
     */
    public function processPaymentCallback(Request $request)
    {
        Log::info('--- --- ---   ...[CONTROLLER - PAYMENT]: Request Purchase...   --- --- ---', $request->all());

        $request->validate([
            "payments" => "required|array",
            "phone" => "phone:KE",
            "provider" => "string"
        ]);

        // TODO: Will this work?
        $transactions = Transaction::findMany(Arr::pluck($request->payments, "payable_id"));

        TransactionRepository::requestPurchase($transactions, $request->all());
    }
}
