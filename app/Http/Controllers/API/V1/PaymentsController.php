<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;
use Throwable;

class PaymentsController extends Controller
{
    use ApiResponse;

    public function processCallback(Request $request)
    {
        $request->validate([
            'payable_type' => ['required'],
            'payable_id'   => ['required'],
            'status'       => ['required', new Enum(Status::class)]
        ]);

        $payable = match ($request->input('payable_type')) {
            "TRANSACTION" => Transaction::findOrFail($request->input('payable_id'))
        };

        $payable->status = Status::tryFrom($request->input("status"));
        $payable->save();
    }

    /**
     * @throws Throwable
     */
    public function requestPurchase(Request $request)
    {
        Log::info('--- --- ---   ...[CONTROLLER - PAYMENT]: Request Purchase...   --- --- ---', $request->all());

        $request->validate([
            "payments" => "required|array",
            "phone"    => "phone:KE",
            "provider" => "string"
        ]);

        TransactionRepository::requestPurchase($request->all());
    }
}
