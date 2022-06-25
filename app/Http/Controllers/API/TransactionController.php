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
        $transactions = Transaction::latest()->get();

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

        dump_json($transactions->toArray());

        return TransactionResource::collection($transactions);
    }
}
