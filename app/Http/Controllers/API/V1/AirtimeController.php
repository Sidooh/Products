<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Events\AirtimePurchaseFailedEvent;
use App\Events\AirtimePurchaseSuccessEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\AirtimeRequest;
use App\Http\Requests\ProductRequest;
use App\Models\AirtimeResponse;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirtimeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param ProductRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function __invoke(AirtimeRequest $request): JsonResponse
    {
        $data = $request->all();

        $account = SidoohAccounts::find($data['account_id']);

        $transactions = [
            [
                "destination" => $data['target_number'] ?? $account["phone"],
                "initiator"   => $data["initiator"],
                "amount"      => $data["amount"],
                "type"        => TransactionType::PAYMENT,
                "description" => Description::AIRTIME_PURCHASE,
                "account_id"  => $data['account_id'],
                "account"     => $account,
            ]
        ];
        $data += [
            "payment_account" => $account,
            "product"         => "airtime",
            "method"          => $data['method'] ?? PaymentMethod::MPESA->value,
        ];

        if($request->input("initiator") === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transactionIds = TransactionRepository::createTransaction($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Airtime Request Successful!');
    }

    public function bulk(AirtimeRequest $request): JsonResponse
    {
        $data = $request->all();

        $transactions = array_map(function($recipient) use ($data) {
            $account = SidoohAccounts::find($recipient['account_id']);

            return [
                "destination" => $account["phone"],
                "initiator"   => $data["initiator"],
                "amount"      => $recipient["amount"],
                "type"        => TransactionType::PAYMENT,
                "description" => Description::AIRTIME_PURCHASE,
                "account_id"  => $data['account_id'],
                "account"     => $account,
            ];
        }, $data['recipients_data']);

        $data = [
            "payment_account" => SidoohAccounts::find($data['account_id']),
            "product"         => "airtime",
            "method"          => $data['method'] ?? PaymentMethod::MPESA->value,
        ];

        $transactionIds = TransactionRepository::createTransaction($transactions, $data);

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
