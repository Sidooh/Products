<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Description;
use App\Enums\EventType;
use App\Enums\PaymentMethod;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Repositories\TransactionRepository;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    /**
     * @throws Exception|\Throwable
     */
    public function __invoke(SubscriptionRequest $request): JsonResponse
    {
        $data = $request->all();

        $account = SidoohAccounts::find($data['account_id']);

        $subscriptionType = SubscriptionType::find($data['subscription_type_id']);

        // Check Subscription doesn't exist
        $subscription = Subscription::whereAccountId($account['id'])->latest()->first();
        if ($subscription && $subscription->status === Status::ACTIVE->name)
            return $this->errorResponse('Account has an existing active subscription', 400);

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
            "method" => $request->has("method") ? PaymentMethod::from($request->input("method")) : PaymentMethod::MPESA,
        ];

        // TODO: Also ensure we can't use other voucher here
        if ($request->has("debit_account")) $data["debit_account"] = $request->input("debit_account");

        $transactionIds = TransactionRepository::createTransactions($transactions, $data);

        return $this->successResponse(['transactions' => $transactionIds], 'Subscription Request Successful!');
    }

    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(Request $request): JsonResponse
    {
        $relations = explode(",", $request->query("with"));

        $subscriptions = Subscription::select([
            "id",
            "start_date",
            "end_date",
            "status",
            "account_id",
            "subscription_type_id",
            "created_at"
        ])->latest()->with("subscriptionType:id,title,price,duration,active,period")->get();

        if (in_array("account", $relations)) {
            $subscriptions = withRelation("account", $subscriptions, "account_id", "id");
        }

        return $this->successResponse($subscriptions);
    }

    public function getSubTypes(): JsonResponse
    {
        $subTypes = SubscriptionType::select([
            "id",
            "title",
            "price",
            "level_limit",
            "duration",
            "active",
            "period",
        ])->latest()->get();

        return $this->successResponse($subTypes);
    }

    public function checkExpiry(): JsonResponse
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

        $expiredSubs = collect();

        $pastSubs->each(function ($sub) use ($expiredSubs) {
            $daysPast = now()->diffInDays($sub->end_date);
            $sub['x'] = $daysPast;
            $sub['y'] = $sub->end_date->diffForHumans();

            if ($sub->status !== Status::EXPIRED) {
                $expiredSubs->add($sub);
            }

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

        $adminMsg = "STATUS:SUBSCRIPTIONS\n\n";
        $adminMsg .= "Processed: {$latestSubscriptions->count()}";

        // Process expired Subs
        if ($expiredCount = $expiredSubs->count()) {
            $expiredSubsAccs = collect();

            $expiredSubs->each(function ($sub) use ($expiredSubsAccs) {
                $account = SidoohAccounts::find($sub->account_id);
                $expiredSubsAccs->add($account);
            });

            $expiredSubs->update(['status' => Status::EXPIRED]);

            $message = "Your subscription to Sidooh has expired.\n\n";
            $message .= "Dial *384*99# NOW for FREE on your Safaricom line to renew your subscription and continue to ";
            $message .= "earn commissions on airtime and tokens purchased by your invited friends and sub-agents";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify($expiredSubsAccs->pluck('phone')->toArray(), $message, EventType::SUBSCRIPTION_EXPIRY);
            $adminMsg .= "\nExpired: $expiredCount";
        }


        // Notify admin
        SidoohNotify::notify([
            '254714611696',
            '254711414987',
            '254110039317'
        ], $adminMsg, EventType::STATUS_UPDATE);


        return $this->successResponse([$pastSubs, $futureSubs, $expiredSubs]);
    }
}
