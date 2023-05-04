<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\PaymentSubtype;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use DrH\Tanda\Library\EventHelper as TandaEventHelper;
use DrH\Tanda\Models\TandaRequest;
use Exception;
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
        $request->validate([
            'page'      => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|between:10,1000',
        ]);

        // TODO: Review using laravel query builder // or build our own params
        $relations = $request->string('with')->explode(',');
        $perPage = $request->integer('page_size', 100);
        $page = $request->integer('page', 1);

        $transactions = Transaction::select([
            'id',
            'amount',
            'charge',
            'status',
            'destination',
            'description',
            'account_id',
            'product_id',
            'created_at',
            'updated_at',
        ])->with('product:id,name')->latest()->limit($perPage)->offset($perPage * ($page - 1))->get();

        if ($relations->contains('account')) {
            $transactions = withRelation('account', $transactions, 'account_id', 'id');
        }

        return $this->successResponse(paginate($transactions, Transaction::count(), $perPage, $page));
    }

    /**
     * @throws \Exception
     */
    public function show(Request $request, Transaction $transaction): JsonResponse
    {
        $relations = explode(',', $request->query('with'));

        if ($transaction->type === TransactionType::WITHDRAWAL) {
            $transaction->load('savingsTransaction:id,transaction_id,savings_id,amount,description,type,status');
        }

        if (in_array('account', $relations)) {
            $transaction->account = SidoohAccounts::find($transaction->account_id);
        }

        if (in_array('payment', $relations)) {
            $transaction->load('payment:id,payment_id,transaction_id,amount,charge,type,subtype,status,created_at,updated_at');
        }

        if (in_array('tanda_request', $relations)) {
            $transaction->load(
                'tandaRequests:request_id,relation_id,receipt_number,amount,provider,destination,message,status,last_modified,created_at,updated_at'
            );
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
            return $transaction->tandaRequests->isEmpty() ? $this->errorResponse(
                'There is a problem with this transaction. Contact Support.'
            ) : $this->successResponse($transaction);
        }

        // Check request id is not in tanda request
        if (TandaRequest::whereRequestId($request->request_id)->doesntExist()) {
            $this->errorResponse('This request id already exists. Contact Support.');
        }

        // Check request
        TransactionRepository::checkRequestStatus($transaction, $request->request_id);

        // return response
        $transaction->refresh()->load('tandaRequests');

        return $this->successResponse($transaction);
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException|Throwable
     */
    public function checkPayment(Request $request, Transaction $transaction): JsonResponse
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
        if (! $transaction->payment) {
            if (! $request->filled('payment_id')) {
                return $this->errorResponse('payment_id is required', 422);
            }

            // Check payment id is not in payments request
            if (Payment::wherePaymentId($request->payment_id)->exists()) {
                $this->errorResponse('This payment id already exists. Contact Support.');
            }

            $payment = SidoohPayments::find($request->payment_id);
        } else {
            $payment = SidoohPayments::find($transaction->payment->payment_id);
        }

        if (! $payment) {
            return $this->errorResponse('There was a problem with your request. Payment not found. Kindly contact Support.');
        }

        if (! $transaction->payment) {
            if ($transaction->amount != $payment['amount'] || $transaction->description != $payment['description']) {
                return $this->errorResponse('Transaction does not match payment.');
            }

            $debitAccount = match (PaymentSubtype::from($payment['subtype'])) {
                PaymentSubtype::STK     => $payment['provider']['phone'],
                PaymentSubtype::VOUCHER => $payment['provider']['id'],
                default                 => throw new Exception('Unable to trace debit account.')
            };

            $paymentData = [
                'transaction_id' => $transaction->id,
                'payment_id'     => $payment['id'],
                'amount'         => $payment['amount'],
                'type'           => $payment['type'],
                'subtype'        => $payment['subtype'],
                'status'         => $payment['status'],
                'extra'          => [
                    'debit_account' => $debitAccount,
                    ...($payment['destination_data'] ?? []),
                ],
            ];

            Payment::create($paymentData);

            $transaction = $transaction->refresh();
        }

        if ($payment['status'] === Status::COMPLETED->name) {
            TransactionRepository::handleCompletedPayment($transaction);
        } elseif ($payment['status'] === Status::FAILED->name) {
            TransactionRepository::handleFailedPayment($transaction, (object) $payment);
        }

        return $this->successResponse($transaction->refresh());
    }

    /**
     * @throws \Exception
     */
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
        if ($transaction->tandaRequests->isNotEmpty() && $transaction->tandaRequests->every(function ($r) {
            return $r->status != 500000;
        })) {
            return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
        }

        // Perform Refund
        TransactionRepository::refundTransaction($transaction);

        return $this->successResponse($transaction->refresh());
    }

    /**
     * @throws \Throwable
     */
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
        if ($transaction->tandaRequests->isNotEmpty()) {
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
        if ($transaction->tandaRequests->isNotEmpty()) {
            if ($transaction->tandaRequests->every(fn($r) => $r->status !== '000000')) {
                return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
            }

            $transaction->updateStatus(Status::COMPLETED);
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
        if ($transaction->tandaRequests->isNotEmpty()) {
            if ($transaction->tandaRequests->every(fn($r) => $r->status == 000000)) {
                return $this->errorResponse('There is a problem with this transaction - Request. Contact Support.');
            }

            TandaEventHelper::fireTandaEvent($transaction->tandaRequests->firstWhere('status', '!=', '000000'));
        } else {
            $transaction->updateStatus(Status::FAILED);
        }

        return $this->successResponse($transaction->refresh());
    }
}
