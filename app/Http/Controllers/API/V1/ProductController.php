<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Tanda\TandaApi;
use App\Http\Controllers\Controller;
use App\Models\AirtimeAccount;
use App\Models\EarningAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\UtilityAccount;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProductController extends Controller
{
    /**
     * @throws Exception
     */
    public function getAccountDetails(int $accountId): JsonResponse
    {
        $account = SidoohAccounts::find($accountId);

        $totalTransactions = Transaction::whereAccountId($accountId)->count();

        $totalTransactionsToday = Transaction::whereAccountId($accountId)->whereDate('created_at', Carbon::today())
            ->count();
        $totalTransactionsWeek = Transaction::whereAccountId($accountId)->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ])->count();
        $totalTransactionsMonth = Transaction::whereAccountId($accountId)->whereBetween('created_at', [
            Carbon::now()->startOfMonth(),
            Carbon::now()->endOfMonth(),
        ])->count();

        $transactions = Transaction::whereAccountId($accountId)->whereType(TransactionType::PAYMENT)
            ->whereNot('product_id', ProductType::VOUCHER)->latest()->get();

        $completedTransactions = $transactions->where('status', Status::COMPLETED->value);

        $totalRevenue = $completedTransactions->sum('amount');
        $totalRevenueToday = $completedTransactions->filter(fn($item) => $item->created_at->isToday())->sum('amount');
        $totalRevenueWeek = $completedTransactions->filter(fn($item) => $item->created_at->isCurrentWeek())
            ->sum('amount');
        $totalRevenueMonth = $completedTransactions->filter(fn($item) => $item->created_at->isCurrentMonth())
            ->sum('amount');

        $voucher = SidoohPayments::findVoucherByAccount($accountId);
        $earningAccounts = EarningAccount::whereAccountId($accountId)->get();
        $subscriptions = Subscription::whereAccountId($accountId)->with('subscriptionType:id,title')->latest()->get();

        $data = [
            'account' => $account,

            'totalTransactionsToday' => $totalTransactionsToday,
            'totalTransactionsWeek'  => $totalTransactionsWeek,
            'totalTransactionsMonth' => $totalTransactionsMonth,
            'totalTransactions'      => $totalTransactions,

            'totalRevenueToday' => $totalRevenueToday,
            'totalRevenueWeek'  => $totalRevenueWeek,
            'totalRevenueMonth' => $totalRevenueMonth,
            'totalRevenue'      => $totalRevenue,

            'recentTransactions' => $transactions,

            'voucher'         => $voucher[0] ?? ['balance' => 0],
            'earningAccounts' => $earningAccounts,
            'subscriptions'   => $subscriptions,
        ];

        return $this->successResponse($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @param  int  $accountId
     * @return JsonResponse
     *
     * @throws AuthenticationException
     */
    public function getAllAirtimeAccounts(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));
        $accounts = AirtimeAccount::select(['id', 'provider', 'priority', 'account_id', 'account_number', 'created_at'])
            ->latest()->get();

        if (in_array('account', $relations)) {
            $accounts = withRelation('account', $accounts, 'account_id', 'id');
        }

        return $this->successResponse($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @throws AuthenticationException
     */
    public function getAllUtilityAccounts(Request $request): JsonResponse
    {
        $relations = explode(',', $request->query('with'));
        $accounts = UtilityAccount::select(['id', 'provider', 'priority', 'account_id', 'account_number', 'created_at'])
            ->latest()->get();

        if (in_array('account', $relations)) {
            $accounts = withRelation('account', $accounts, 'account_id', 'id');
        }

        return $this->successResponse($accounts);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @param  int  $accountId
     * @return JsonResponse
     */
    public function airtimeAccounts(Request $request, int $accountId): JsonResponse
    {
        $accounts = AirtimeAccount::select(['id', 'provider', 'account_number'])->whereAccountId($accountId);

        if ($request->exists('limit')) {
            $accounts = $accounts->limit($request->input('limit'));
        }

        $accounts = $accounts->latest()->get();

        return $this->successResponse($accounts);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @param  int  $accountId
     * @return JsonResponse
     */
    public function utilityAccounts(Request $request, int $accountId): JsonResponse
    {
        $accounts = UtilityAccount::select(['id', 'provider', 'account_number'])->whereAccountId($accountId);

        if ($request->exists('limit')) {
            $accounts = $accounts->limit($request->input('limit'));
        }

        $accounts = $accounts->latest()->get();

        return $this->successResponse($accounts);
    }

    public function currentSubscription(Request $request, int $accountId): JsonResponse
    {
        // TODO: Handle for subscription not found first or fail?
        $subscription = Subscription::whereAccountId($accountId)->latest()->firstOrFail();

        return $this->successResponse($subscription);
    }

    public function earnings(Request $request, int $accountId): JsonResponse
    {
        $earnings = EarningAccount::select(['type', 'self_amount', 'invite_amount'])->whereAccountId($accountId)->get();

        return $this->successResponse($earnings);
    }

    public function getEarningRates(Request $request): JsonResponse
    {
        $provider = mb_strtolower(config('services.sidooh.utilities_provider'));
        $discounts = config("services.$provider.discounts");

        return $this->successResponse($discounts);
    }

    public function getServiceProviderBalance(): JsonResponse
    {
        return $this->successResponse([
            'tanda' => TandaApi::balance(),
            'at'    => AfricasTalkingApi::balance(),
        ]);
    }
}
