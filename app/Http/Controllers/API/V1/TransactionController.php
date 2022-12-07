<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Status;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\EventHelper as TandaEventHelper;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // TODO: Review using laravel query builder // or build our own params
        $relations = explode(',', $request->query('with'));
        $transactions = Transaction::select([
            'id',
            'amount',
            'status',
            'destination',
            'description',
            'account_id',
            'product_id',
            'created_at',
            'updated_at',
        ])->with('product:id,name');

        if ($request->has('status') && $status = Status::tryFrom($request->status)) {
            $transactions->whereStatus($status);
        }

        $transactions = $transactions->latest()->limit(100)->get();

        // TODO: pagination will not work with the process below - review fix for it
        if (in_array('account', $relations)) {
            $transactions = withRelation('account', $transactions, 'account_id', 'id');
        }

        return $this->successResponse($transactions);
    }

    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if($transaction->type === TransactionType::WITHDRAWAL) {
            $transaction->load("savingsTransaction:id,transaction_id,savings_id,amount,description,type,status");
        }

        if (in_array('account', $relations)) {
            $transaction->account = SidoohAccounts::find($transaction->account_id);
        }

        if (in_array('payment', $relations)) {
            $transaction->load('payment:id,payment_id,transaction_id,amount,type,subtype,status,created_at,updated_at');
        }

        if (in_array('tanda_request', $relations)) {
            $transaction->load('tandaRequest:request_id,relation_id,receipt_number,amount,provider,destination,message,status,last_modified,created_at,updated_at');
        }

        if (in_array('product', $relations)) {
            $transaction->load('product:id,name');
        }

        return $this->successResponse($transaction);
    }

    public function checkRequest(Request $request, Transaction $transaction): JsonResponse
    {
        if (! $request->has('request_id') || $request->request_id == '') {
            return $this->errorResponse('request_id is required', 422);
        }

        // Check transaction is PENDING ...
        if ($transaction->status !== Status::PENDING->name) {
            return ! $transaction->tandaRequest
                ? $this->errorResponse('There is a problem with this transaction. Contact Support.')
                : $this->successResponse($transaction);
        }

        // Check request id is not in tanda request
        if (TandaRequest::whereRequestId($request->request_id)->doesntExist()) {
            $this->errorResponse('This request id already exists. Contact Support.');
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
    public function checkPayment(Transaction $transaction): JsonResponse
    {
        // Check transaction is PENDING ...
        if ($transaction->status !== Status::PENDING->name) {
            if (! $transaction->payment) {
                return $this->errorResponse('There is a problem with this transaction. Contact Support.');
            } elseif ($transaction->payment?->status !== Status::PENDING->name) {
                return $this->successResponse($transaction->refresh());
            }
        }

        // Check payment
        $response = SidoohPayments::find($transaction->payment->payment_id);

        if (! $payment = $response) {
            return $this->errorResponse('There was a problem with your request. Kindly contact Support.');
        }

        if ($payment['status'] === Status::COMPLETED->name) {
            TransactionRepository::handleCompletedPayment($transaction);
        } elseif ($payment['status'] === Status::FAILED->name) {
            TransactionRepository::handleFailedPayment($transaction, (object) $payment);
        }

        return $this->successResponse($transaction->refresh());
    }

    public function refund(Transaction $transaction): JsonResponse
    {
        // Check transaction
        if ($transaction->status !== Status::PENDING->name) {
            return $this->errorResponse('There is a problem with this transaction - Status. Contact Support.');
        }

        // Check payment
        if (! $transaction->payment) {
            return $this->errorResponse('There is a problem with this transaction - Payment. Contact Support.');
        }

        // Check request
        if ($transaction->tandaRequest) {
            return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
        }

        // Perform Refund
        TransactionRepository::refundTransaction($transaction);

        return $this->successResponse($transaction->refresh());
    }

    public function retry(Transaction $transaction): JsonResponse
    {
        // Check transaction
        if ($transaction->status !== Status::PENDING->name) {
            return $this->errorResponse('There is a problem with this transaction - Status. Contact Support.');
        }

        // Check payment
        if ($transaction->payment?->status != Status::COMPLETED->name) {
            return $this->errorResponse('There is a problem with this transaction - Payment. Contact Support.');
        }

        // Check request
        if ($transaction->tandaRequest) {
            return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
        }

        // Perform Refund
        TransactionRepository::requestPurchase($transaction);

        return $this->successResponse($transaction->refresh());
    }

    public function complete(Transaction $transaction): JsonResponse
    {
        // Check transaction
        if ($transaction->status !== Status::PENDING->name) {
            return $this->errorResponse('There is a problem with this transaction - Status. Contact Support.');
        }

        // Check payment
        if (! $transaction->payment || $transaction->payment->status !== Status::COMPLETED->name) {
            return $this->errorResponse('There is a problem with this transaction - Payment. Contact Support.');
        }

        // Check request
        // TODO: Handle for all other SPs - and future SPs possibilities
        if ($transaction->tandaRequest) {
            if ($transaction->tandaRequest->status !== '000000') {
                return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
            }

            Transaction::updateStatus($transaction, Status::COMPLETED);
        } else {
            return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
        }

        return $this->successResponse($transaction->refresh());
    }

    public function fail(Transaction $transaction): JsonResponse
    {
        // Check transaction
        if ($transaction->status !== Status::PENDING->name) {
            return $this->errorResponse('There is a problem with this transaction - Status. Contact Support.');
        }

        // Check payment
        if ($transaction->payment && $transaction->payment->status !== Status::FAILED->name) {
            return $this->errorResponse('There is a problem with this transaction - Payment. Contact Support.');
        }

        // Check request
        // TODO: Handle for all other SPs - and future SPs possibilities
        if ($transaction->tandaRequest) {
            if ($transaction->tandaRequest->status === 000000) {
                return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
            }

            TandaEventHelper::fireTandaEvent($transaction->tandaRequest);
        } else {
            Transaction::updateStatus($transaction, Status::FAILED);
        }

        return $this->successResponse($transaction->refresh());
    }
}
