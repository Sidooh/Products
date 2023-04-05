<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\ProductType;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Models\AirtimeAccount;
use App\Models\EarningAccount;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\UtilityAccount;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    /**
     * @throws \Exception
     */
    public function show(int $accountId): JsonResponse
    {
        $account = SidoohAccounts::find($accountId);

        $date = Carbon::today();
        $sW = Carbon::now()->startOfWeek();
        $eW = Carbon::now()->endOfWeek();
        $sM = Carbon::now()->startOfMonth();
        $eM = Carbon::now()->endOfMonth();
        $last30d = Carbon::now()->subMonth();

        $totalTransactions = Transaction::whereAccountId($accountId)->select([
            DB::raw('COUNT(id) as ctotal'),
            DB::raw("SUM(DATE(created_at) = '{$date->toDateString()}') as ctoday"),
            DB::raw("SUM(created_at between '$sW' and '$eW') as cweek"),
            DB::raw("SUM(created_at between '$sM' and '$eM') as cmonth"),
            DB::raw("SUM(created_at > '$last30d') as c30"),
        ])->first();

        $transactions = Transaction::whereAccountId($accountId)
            ->whereNot('product_id', ProductType::VOUCHER)->latest()->get();

        $completedTransactions = $transactions->where('status', Status::COMPLETED->value);

        $totalRevenue = $totalRevenueToday = $totalRevenueWeek = $totalRevenueMonth = $totalRevenue30d = 0;
        foreach ($completedTransactions as $t) {
            $totalRevenue += $t->amount;
            $totalRevenueToday += $t->created_at->isToday() ? $t->amount : 0;
            $totalRevenueWeek += $t->created_at->isCurrentWeek() ? $t->amount : 0;
            $totalRevenueMonth += $t->created_at->isCurrentMonth() ? $t->amount : 0;
            // TODO: Implement for 30d
//            $totalRevenue30d += $t->created_at->isCurrentMonth() ? $t->amount : 0;
        }

        try {
            $vouchers = SidoohPayments::findVouchersByAccount($accountId);
        } catch (Exception) {
            $vouchers = [];
        }

        $earningAccounts = EarningAccount::whereAccountId($accountId)->get();
        $subscriptions = Subscription::whereAccountId($accountId)->with('subscriptionType:id,title')->latest()->get();

        $data = [
            'account'                => $account,

            'totalTransactionsToday' => $totalTransactions->ctoday,
            'totalTransactionsWeek'  => $totalTransactions->cweek,
            'totalTransactionsMonth' => $totalTransactions->cmonth,
            'totalTransactions30d'   => $totalTransactions->c30,
            'totalTransactions'      => $totalTransactions->ctotal,

            'totalRevenueToday'      => $totalRevenueToday,
            'totalRevenueWeek'       => $totalRevenueWeek,
            'totalRevenueMonth'      => $totalRevenueMonth,
            'totalRevenue30d'        => $totalRevenue30d,
            'totalRevenue'           => $totalRevenue,

            'recentTransactions'     => $transactions,

            'vouchers'               => $vouchers,
            'earningAccounts'        => $earningAccounts,
            'subscriptions'          => $subscriptions,
        ];

        return $this->successResponse($data);
    }

    // TODO: Use repo pattern for this and utilities?
    public function airtimeAccounts(Request $request, int $accountId): JsonResponse
    {
        $accounts = Cache::rememberForever("airtime_accounts_$accountId", function() use ($accountId, $request) {
            $accounts = AirtimeAccount::select(['id', 'provider', 'account_number'])->whereAccountId($accountId);

            if ($request->filled('limit')) {
                $accounts = $accounts->limit($request->integer('limit'));
            }

            return $accounts->latest()->get();
        });

        return $this->successResponse($accounts);
    }

    public function utilityAccounts(Request $request, int $accountId): JsonResponse
    {
        $accounts = Cache::rememberForever("utility_accounts_$accountId", function() use ($request, $accountId) {
            $accounts = UtilityAccount::select(['id', 'provider', 'account_number'])->whereAccountId($accountId);

            if ($request->filled('limit')) {
                $accounts = $accounts->limit($request->integer('limit'));
            }

            return $accounts->latest()->get();
        });

        return $this->successResponse($accounts);
    }

    public function currentSubscription(int $accountId): JsonResponse
    {
        // TODO: Handle for subscription not found first or fail?
        $subscription = Subscription::whereAccountId($accountId)->latest()->firstOrFail();

        return $this->successResponse($subscription);
    }

    public function earnings(int $accountId): JsonResponse
    {
        $earnings = EarningAccount::select(['type', 'self_amount', 'invite_amount'])->whereAccountId($accountId)->get();

        return $this->successResponse($earnings);
    }
}
