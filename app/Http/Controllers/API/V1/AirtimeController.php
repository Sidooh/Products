<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Events\AirtimePurchaseFailedEvent;
use App\Events\AirtimePurchaseSuccessEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AirtimeRequest;
use App\Models\AirtimeResponse;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AirtimeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param AirtimeRequest $request
     * @throws Exception|Throwable
     * @return JsonResponse
     */
    public function __invoke(AirtimeRequest $request): JsonResponse
    {
        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        $transactionsData = [
            [
                "destination" => $data['target_number'] ?? $account["phone"],
                "initiator" => $data["initiator"],
                "amount" => $data["amount"],
                "type" => TransactionType::PAYMENT,
                "description" => Description::AIRTIME_PURCHASE,
                "account_id" => $data['account_id'],
                "product_id" => ProductType::AIRTIME,
                "account" => $account,
            ]
        ];
        $data = [
            "payment_account" => $account,
            "method" => $request->has("method") ? PaymentMethod::from($request->input("method")) : PaymentMethod::MPESA,
        ];

        if($request->has("debit_account")) $data["debit_account"] = $request->input("debit_account");
//        if($request->input("initiator") === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transactionIds = TransactionRepository::createTransactions($transactionsData, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Airtime Request Successful!');
    }

    public function bulk(AirtimeRequest $request): JsonResponse
    {
        $data = $request->all();

        $transactions = array_map(function($recipient) use ($data) {
            $account = SidoohAccounts::find($recipient['account_id']);

            return [
                "destination" => $account["phone"],
                "initiator" => $data["initiator"],
                "amount" => $recipient["amount"],
                "type" => TransactionType::PAYMENT,
                "description" => Description::AIRTIME_PURCHASE,
                "account_id" => $data['account_id'],
                "product_id" => ProductType::AIRTIME,
                "account" => $account,
            ];
        }, $data['recipients_data']);

        $data = [
            "payment_account" => SidoohAccounts::find($data['account_id']),
            "product"         => "airtime",
            "method"          => $data['method'] ?? PaymentMethod::MPESA->value,
        ];

        $transactionIds = TransactionRepository::createTransactions($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Bulk Airtime Request Successful!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return void
     */
    public function airtimeStatusCallback(Request $request): void
    {
        $callback = $request->all();

        $res = AirtimeResponse::whereRequestId($callback['requestId'])->firstOrFail();

        if($res->status != 'Success') {
            $res->status = Status::tryFrom($callback['status']) ?? strtoupper($callback['status']);
            $res->save();

            $this->fireAirtimePurchaseEvent($res, $callback);
        }
    }

    private function fireAirtimePurchaseEvent(AirtimeResponse $response, array $callback)
    {
        $callback['status'] == 'Success'
            ? AirtimePurchaseSuccessEvent::dispatch($response)
            : AirtimePurchaseFailedEvent::dispatch($response);
    }
}
