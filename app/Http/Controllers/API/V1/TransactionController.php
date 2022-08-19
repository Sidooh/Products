<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

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

        if($request->has('status') && $status = Status::tryFrom($request->status)) {
            $transactions->whereStatus($status);
            if($status !== Status::PENDING) {
                $transactions->limit(100); // Other statuses will have too many records
            }
        } else {
            $transactions->limit(100);
        }

        $transactions = $transactions->latest()->get();

        // TODO: pagination will not work with the process below - review fix for it
        if(in_array("account", $relations)) $transactions = withRelation("account", $transactions, "account_id", "id");

        return $this->successResponse($transactions);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        if(in_array("account", $relations)) {
            $transaction->account = SidoohAccounts::find($transaction->account_id, true);
        }

        if(in_array("payment", $relations)) {
            $transaction->load("payment:id,payment_id,transaction_id,amount,type,subtype,status,created_at,updated_at");
        }

        if(in_array("tanda_request", $relations)) {
            $transaction->load("tandaRequest:request_id,relation_id,receipt_number,amount,provider,destination,message,status,last_modified,created_at,updated_at");
        }

        if(in_array("product", $relations)) $transaction->load("product:id,name");

        return $this->successResponse($transaction);
    }

    public function checkRequest(Request $request, Transaction $transaction): JsonResponse
    {
        if (!$request->has('request_id') || $request->request_id == "") return $this->errorResponse("request_id is required", 422);

        // Check transaction is PENDING ...
        if ($transaction->status !== Status::PENDING->name) return !$transaction->tandaRequest
            ? $this->errorResponse("There is a problem with this transaction. Contact Support.")
            : $this->successResponse($transaction);

        // Check request id is not in tanda request
        if (TandaRequest::whereRequestId($request->request_id)->doesntExist()) {
            $this->errorResponse("This request id already exists. Contact Support.");
        }

        // Check request
        TransactionRepository::checkRequestStatus($transaction, $request->request_id);

        // return response
        $transaction->refresh()->load('tandaRequest');

        return $this->successResponse($transaction);
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException|Throwable
     */
    public function checkPayment(Request $request, Transaction $transaction): JsonResponse
    {
        // Check transaction is PENDING ...
        if($transaction->status !== Status::PENDING->name) if(!$transaction->payment) return $this->errorResponse("There is a problem with this transaction. Contact Support."); else if($transaction->payment?->status !== Status::PENDING->name) return $this->successResponse($transaction->refresh());

        // Check payment
        $response = SidoohPayments::find($transaction->payment->payment_id);

        if(!$payment = $response) {
            return $this->errorResponse("There was a problem with your request. Kindly contact Support.");
        }

        if($payment['status'] === Status::COMPLETED->name) {
            TransactionRepository::handleCompletedPayments(collect([$transaction]), collect([$payment]));
        } else if($payment['status'] === Status::FAILED->name) {
            TransactionRepository::handleFailedPayments(collect([$transaction]), collect([$payment]));
        }

        return $this->successResponse($transaction->refresh());
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function refund(Request $request, Transaction $transaction): JsonResponse
    {
        // Check transaction
        if($transaction->status !== Status::PENDING->name) {
            return $this->errorResponse("There is a problem with this transaction - Status. Contact Support.");
        }

        // Check payment
        if(!$transaction->payment) {
            return $this->errorResponse("There is a problem with this transaction - Payment. Contact Support.");
        }

        // Check request
        if($transaction->tandaRequest) {
            return $this->errorResponse("There is a problem with this transaction - Request. Contact Support.");
        }

        // Perform Refund
        TransactionRepository::refundTransaction($transaction);

        return $this->successResponse($transaction->refresh());
    }
}
