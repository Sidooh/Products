<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * @throws Exception|\Throwable
     */
    public function __invoke(ProductRequest $request): JsonResponse
    {
        $data = $request->all();

        $account = SidoohAccounts::find($data['account_id']);

        $subscriptionType = SubscriptionType::find($data['subscription_type_id']);

        //Check Subscription doesn't exist
        $subscription = Subscription::whereAccountId($account['id'])->latest()->first();
        if ($subscription && $subscription->status === Status::ACTIVE)
            return $this->errorResponse('Subscription already exists');

        $transactions = [
            [
                "initiator" => $data["initiator"],
                "amount" => $subscriptionType->price,
                "destination" => $data['target_number'] ?? $account["phone"],
                "type" => TransactionType::PAYMENT,
                "description" => Description::SUBSCRIPTION_PURCHASE,
                "account_id" => $data['account_id'],
                "product_id" => ProductType::SUBSCRIPTION,
                "account" => $account,
            ]
        ];

        $data = [
            "payment_account" => $account,
            "method" => $data['method'] ?? PaymentMethod::MPESA->value,
        ];

        // TODO: Also ensure we can't use other voucher here
        if ($request->has("debit_account") && $data['method'] === PaymentMethod::MPESA->value)
            $data["debit_account"] = $request->input("debit_account");

        $transactionIds = TransactionRepository::createTransactions($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Subscription Request Successful!');
    }

    public function checkExpiry()
    {
        // TODO: What if end_date order is not id order? 1,2022-07-07 12:02:59; 2,2022-07-04 12:02:59 ...?

        // Get latest IDs
        $latestSubscriptions = Subscription::select('account_id', DB::raw('MAX(id) as latest_subscription_id'))
            ->includePreExpiry()
            ->orWhere
            ->includePostExpiry()
            ->groupBy('account_id');

        // Get subscription data based on latest IDs sub query
        $requiredSubcriptions = Subscription::joinSub($latestSubscriptions, 'latest_subs',
            fn($join) => $join->on('id', '=', 'latest_subs.latest_subscription_id')
        )->get();

        [$pastSubs, $futureSubs] = $requiredSubcriptions->partition(fn($s) => $s->end_date < now());

        $pastSubs->each(function ($sub) {
            $daysPast = now()->diffInDays($sub->end_date);
            $sub['x'] = $daysPast;
            $sub['y'] = $sub->end_date->diffForHumans();

            if (in_array($daysPast, [2, 3, 4, 6])) {
                //notify expired...

                $message = "Subscription expired {$sub->end_date->diffForHumans()}\n\n";
                $message .= config('services.sidooh.tagline');

                $account = SidoohAccounts::find($sub->account_id);
                SidoohNotify::notify([$account['phone']], $message, EventType::SUBSCRIPTION_EXPIRY);
            }
        });

        $futureSubs->each(function ($sub) {
            $daysLeft = now()->diffInDays($sub->end_date);
            $sub['x'] = $daysLeft;
            $sub['y'] = $sub->end_date->diffForHumans();

            if (in_array($daysLeft, [5, 3, 2, 1])) {
                //notify expiry due...

                $message = "Subscription will expire {$sub->end_date->diffForHumans()}\n\n";
                $message .= config('services.sidooh.tagline');

                $account = SidoohAccounts::find($sub->account_id);
                SidoohNotify::notify([$account['phone']], $message, EventType::SUBSCRIPTION_EXPIRY);
            }
        });

        Log::info('...[SUB_CTRL]... Subs: ', [now(), $pastSubs->toArray(), $futureSubs->toArray()]);

        return $this->successResponse([$pastSubs, $futureSubs]);
    }
}
