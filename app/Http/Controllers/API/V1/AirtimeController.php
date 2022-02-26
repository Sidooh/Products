<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Events\AirtimePurchaseFailedEvent;
use App\Events\AirtimePurchaseSuccessEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\AirtimeResponse;
use App\Models\Transaction;
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
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $data = $request->all();
        $data += [
            "account"     => SidoohAccounts::find($data['account_id']),
            "product"     => "airtime",
            "method"      => $data['method'] ?? PaymentMethod::MPESA->value,
            "type"        => TransactionType::PAYMENT,
            "description" => Description::AIRTIME_PURCHASE
        ];

        if($data['initiator'] === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transaction = $this->init($data, $data['account']);

        return $this->successResponse(['transaction_id' => $transaction->id], 'Airtime Request Successful');
    }

    /**
     * @throws Exception
     */
    public function init($data, $account): Transaction
    {
        $data['destination'] = $data['target_number'] ?? $account['phone'];

        return TransactionRepository::createTransaction($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @return void
     */
    public function airtimeStatusCallback(Request $request)
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
