<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\VoucherRequest;
use App\Models\Enterprise;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use Exception;
use Illuminate\Http\JsonResponse;
use Throwable;

class VoucherController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param VoucherRequest $request
     * @return JsonResponse
     * @throws Exception|Throwable
     */
    public function topUp(VoucherRequest $request): JsonResponse
    {
        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        // == and not ===  since they are int and string sometimes
        if ($data['target_number'] && $account['phone'] == $data['target_number']) {
            return $this->errorResponse('Target number cannot be your account phone number', 422);
        }

        $transactions = [
            [
                "destination" => $data['target_number'] ?? $account["phone"],
                "initiator" => $data["initiator"],
                "amount" => $data["amount"],
                "type" => TransactionType::PAYMENT,
                "description" => Description::VOUCHER_PURCHASE,
                "account_id" => $data['account_id'],
                "product_id" => ProductType::VOUCHER,
                "account" => $account,
            ]
        ];

        $data = [
            "payment_account" => $account,
            "method" => $data["method"] ?? PaymentMethod::MPESA->value,
        ];

        // TODO: Also ensure we can't use other voucher here
        if ($request->has("debit_account") && $data['method'] === PaymentMethod::MPESA->value)
            $data["debit_account"] = $request->input("debit_account");

        $transactionIds = TransactionRepository::createTransaction($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Voucher Request Successful!');
    }

    /**
     * @throws Throwable
     */
    public function disburse(VoucherRequest $request): JsonResponse
    {
        $data = $request->all();
        $enterprise = Enterprise::find($data['enterprise_id']);

        if(!isset($data['amount'])) {
            $data['amount'] = match ($data['disburse_type']) {
                "LUNCH" => $enterprise->max_lunch,
                "GENERAL" => $enterprise->max_general
            };

            if(!isset($data['amount'])) {
                return $this->errorResponse("Amount is required! default amount for {$data['disburse_type']} voucher not set");
            }
        }

        if(!isset($data['accounts'])) {
            $data['accounts'] = $enterprise->enterpriseAccounts->pluck('account_id')->toArray();
        }

        SidoohPayments::voucherDisbursement($enterprise->id, $data);

        $message = "{$data['disburse_type']} Voucher Disburse Request Successful";
        return $this->successResponse($data['accounts'], $message);
    }
}
