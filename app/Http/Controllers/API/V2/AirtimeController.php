<?php

namespace App\Http\Controllers\API\V2;

use App\Enums\Description;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\AirtimeRequest;
use App\Repositories\V2\TransactionRepository;
use App\Services\SidoohAccounts;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class AirtimeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  AirtimeRequest  $request
     * @return JsonResponse
     *
     * @throws Exception|Throwable
     */
    public function __invoke(AirtimeRequest $request): JsonResponse
    {
        Log::info('...[CTRL - AIRTIME]: Process Airtime Request...', $request->all());

        $data = $request->validated();

        $account = SidoohAccounts::find($data['account_id']);

        $transactionData = [
            'destination' => $data['target_number'] ?? $account['phone'],
            'initiator'   => $data['initiator'],
            'amount'      => $data['amount'],
            'type'        => TransactionType::PAYMENT,
            'description' => Description::AIRTIME_PURCHASE,
            'account_id'  => $data['account_id'],
            'product_id'  => ProductType::AIRTIME,
            'account'     => $account,
        ];

        $data = [
            'method' => $request->has('method') ? PaymentMethod::from($request->input('method'))
                : PaymentMethod::MPESA,
        ];

        // TODO: Else get default voucher for the person
        if ($request->has('debit_account')) {
            $data['debit_account'] = $request->input('debit_account');
        }
//        if($request->input("initiator") === 'ENTERPRISE') $data['method'] = 'FLOAT';

        $transaction = TransactionRepository::createTransaction($transactionData, $data);

        return $this->successResponse($transaction, 'Airtime Request Successful!');
    }

//    /**
//     * @throws \Illuminate\Auth\AuthenticationException
//     * @throws \Throwable
//     */
//    public function bulk(AirtimeRequest $request): JsonResponse
//    {
//        $data = $request->all();
//
//        $transactions = array_map(function($recipient) use ($data) {
//            $account = SidoohAccounts::find($recipient['account_id']);
//
//            return [
//                'destination' => $account['phone'],
//                'initiator'   => $data['initiator'],
//                'amount'      => $recipient['amount'],
//                'type'        => TransactionType::PAYMENT,
//                'description' => Description::AIRTIME_PURCHASE,
//                'account_id'  => $data['account_id'],
//                'product_id'  => ProductType::AIRTIME,
//                'account'     => $account,
//            ];
//        }, $data['recipients_data']);
//
//        $data = [
//            'payment_account' => SidoohAccounts::find($data['account_id']),
//            'product'         => 'airtime',
//            'method'          => $data['method'] ?? PaymentMethod::MPESA->value,
//        ];
//
//        $transactionIds = TransactionRepository::createTransactions($transactions, $data);
//
//        return $this->successResponse(['transactions' => $transactionIds], 'Bulk Airtime Request Successful!');
//    }
}
