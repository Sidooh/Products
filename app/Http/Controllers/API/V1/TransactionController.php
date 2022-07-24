<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(",", $request->query("with"));
        $transactions = Transaction::select([
            "id",
            "amount",
            "status",
            "destination",
            "account_id",
            "product_id",
            "created_at"
        ])->latest()->with("product:id,name")->get();

        if(in_array("account", $relations)) $transactions = withRelation("account", $transactions, "account_id", "id");

        return $this->successResponse($transactions);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        if (in_array("account", $relations)) {
            $transaction->account = SidoohAccounts::find($transaction->account_id, true);
        }

        if (in_array("payment", $relations)) {
            $transaction->load("payment:id,payment_id,transaction_id,amount,type,subtype,status");
        }

        if (in_array("request", $relations)) {
            $transaction->load("tandaRequest:request_id,relation_id,receipt_number,amount,provider,destination,message,status,last_modified");
        }

        if (in_array("product", $relations)) $transaction->load("product:id,name");

        return $this->successResponse($transaction);
    }
}
