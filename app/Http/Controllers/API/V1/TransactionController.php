<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        // TODO: Review using laravel query builder // or build our own params
        $relations = explode(",", $request->query("with"));
        $transactions = Transaction::select([
            "id",
            "amount",
            "status",
            "destination",
            "description",
            "account_id",
            "product_id",
            "created_at",
            "updated_at"
        ])->with("product:id,name");

        if ($request->has('status') && $status = Status::tryFrom($request->status)) {
            $transactions->whereStatus($status);
            if ($status !== Status::PENDING) {
                $transactions->limit(100); // Other statuses will have too many records
            }
        } else {
            $transactions->limit(100);
        }

        $transactions = $transactions->latest()->get();

        // TODO: pagination will not work with the process below - review fix for it
        if (in_array("account", $relations)) $transactions = withRelation("account", $transactions, "account_id", "id");

        return $this->successResponse($transactions);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        if (in_array("account", $relations)) {
            $transaction->account = SidoohAccounts::find($transaction->account_id, true);
        }

        if (in_array("payment", $relations)) {
            $transaction->load("payment:id,payment_id,transaction_id,amount,type,subtype,status,created_at,updated_at");
        }

        if (in_array("tanda_request", $relations)) {
            $transaction->load("tandaRequest:request_id,relation_id,receipt_number,amount,provider,destination,message,status,last_modified,created_at,updated_at");
        }

        if (in_array("product", $relations)) $transaction->load("product:id,name");

        return $this->successResponse($transaction);
    }

    public function checkRequest(Request $request, Transaction $transaction): JsonResponse
    {
        if (!$request->has('request_id') || $request->request_id == "") return $this->errorResponse("request_id is required", 422);

        // Check transaction is PENDING ...
        if ($transaction->status !== Status::PENDING->name)
            if (!$transaction->tandaRequest)
                // TODO: there is a problem with this transaction
                return $this->errorResponse("There is a problem with this transaction. Contact Support.");
            else
                return $this->successResponse($transaction);

        // Check request
        TransactionRepository::checkRequestStatus($transaction, $request->request_id);

        //TODO: Reimburse if needed. // or have different endpoint?

        // return response
        $transaction->refresh()->load('tandaRequest');

        return $this->successResponse($transaction);
    }

    public function checkPayment(Request $request, Transaction $transaction): JsonResponse
    {
        // Check transaction is PENDING ...
        if ($transaction->status !== Status::PENDING->name)
            if (!$transaction->payment)
                // TODO: there is a problem with this transaction
                return $this->errorResponse("There is a problem with this transaction. Contact Support.");
            elseif ($transaction->payment?->status !== Status::PENDING->name)
                return $this->successResponse($transaction->refresh());

        // Check payment
        $response = SidoohPayments::find($transaction->payment->payment_id);

        if (!$payment = $response['data']) {
            return $this->errorResponse("There was a problem with your request. Contact Support.");
        }

        if ($payment['status'] === Status::COMPLETED->name) {
            TransactionRepository::handleCompletedPayments(collect([$transaction]), collect([$payment]));
        } elseif ($payment['status'] === Status::FAILED->name) {
            TransactionRepository::handleFailedPayments(collect([$transaction]), collect([$payment]));
        }

        return $this->successResponse($transaction->refresh());
    }
}
