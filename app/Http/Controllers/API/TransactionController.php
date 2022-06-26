<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): AnonymousResourceCollection
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

        if(in_array("account", $relations)) {
            $accounts = collect(SidoohAccounts::getAll());

            $transactions->transform(function(Transaction $transaction) use ($accounts) {
                $transaction->account = $accounts->firstWhere("id", $transaction->account_id);
                return $transaction;
            });
        }

        if(in_array("payment", $relations)) {
            $payments = collect(SidoohPayments::getAll());

            $transactions->transform(function(Transaction $transaction) use ($payments) {
                $transaction->payment = $payments->firstWhere("payable_id", $transaction->id);
                return $transaction;
            });
        }

        return TransactionResource::collection($transactions);
    }

    public function show(Request $request, Transaction $transaction): TransactionResource
    {
        $relations = explode(",", $request->query("with"));

        if(in_array("account", $relations)) {
            $transaction->account = SidoohAccounts::find($transaction->account_id, true);
        }

        if(in_array("payment", $relations)) {
            $transaction->payment = SidoohPayments::findByTransactionId($transaction->id);
        }

        if(in_array("tanda_request", $relations)) $transaction->load("request:id,relation_id,receipt_number,amount,provider,destination,message,status,last_modified");

        return TransactionResource::make($transaction);
    }
}
