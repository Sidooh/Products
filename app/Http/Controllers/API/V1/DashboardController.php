<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function summaries(Request $request): JsonResponse
    {
        if ($request->filled('bypass_cache')) {
            $request->string('bypass_cache')->explode(',')->each(fn ($k) => Cache::forget($k));
        }

        $totalTransactions = Cache::remember('total_transactions', 60 * 60 * 24, fn () => Transaction::count());
        $totalTransactionsToday = Cache::remember(
            'total_transactions_today',
            60 * 60,
            fn () => Transaction::whereDate('created_at', Carbon::today())->count()
        );

        $totalRevenue = Cache::remember('total_revenue', 60 * 60 * 24, function() {
            return Transaction::whereStatus(Status::COMPLETED)
                              ->whereType(TransactionType::PAYMENT)
                              ->whereNot('product_id', ProductType::VOUCHER)
                              ->sum('amount');
        });
        $totalRevenueToday = Cache::remember('total_revenue_today', 60 * 60, function() {
            return Transaction::whereStatus(Status::COMPLETED)
                              ->whereType(TransactionType::PAYMENT)
                              ->whereNot('product_id', ProductType::VOUCHER)
                              ->whereDate('created_at', Carbon::today())
                              ->sum('amount');
        });

        return $this->successResponse([
            'total_transactions'       => $totalTransactions,
            'total_transactions_today' => $totalTransactionsToday,

            'total_revenue'       => $totalRevenue,
            'total_revenue_today' => $totalRevenueToday,
        ]);
    }

    public function getChartData(Request $request): JsonResponse
    {
        if ($request->filled('bypass_cache')) {
            Cache::forget('dashboard_chart_data');
        }

        return $this->successResponse(Cache::remember('dashboard_chart_data', (3600 * 3), function() {
            return Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereType(TransactionType::PAYMENT)
                              ->whereDate('created_at', '>=', Carbon::yesterday())
                              ->groupBy('date', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(function($tx) {
                                  $dateIsToday = Carbon::createFromFormat('YmdH', $tx->date)->isToday();

                                  return $dateIsToday ? 'TODAY' : 'YESTERDAY';
                              });
        }));
    }

    /**
     * @throws AuthenticationException
     */
    public function transactions(Request $request): JsonResponse
    {
        // TODO: Review using laravel query builder // or build our own params
        $relations = explode(',', $request->query('with'));
        $columns = [
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
        ];

        $pending = Transaction::select($columns)->with('product:id,name')->whereStatus(Status::PENDING)->get();
        $recent = Transaction::select($columns)->with('product:id,name')->whereNot('status', Status::PENDING)->latest()
                             ->limit(100)->get();

        // TODO: pagination will not work with the process below - review fix for it
        if (in_array('account', $relations)) {
            $pending = withRelation('account', $pending, 'account_id', 'id');
            $recent = withRelation('account', $recent, 'account_id', 'id');
        }

        return $this->successResponse([
            'pending' => $pending,
            'recent'  => $recent,
        ]);
    }

    public function getProviderBalances(Request $request): JsonResponse
    {
        if ($request->filled('bypass_cache')) {
            $request->string('bypass_cache')->explode(',')->each(fn ($k) => Cache::forget($k));
        }

        try {
            $tandaFloatBalance = Cache::remember('tanda_float_balance', (3600), function() {
                return TandaApi::balance()[0]->balances[0]->available;
            });
        } catch (Exception) {
            $tandaFloatBalance = null;
        }

        try {
            $ATAirtimeBalance = Cache::rememberForever('at_airtime_balance', function() {
                return (float) ltrim(AfricasTalkingApi::balance()['data']->UserData->balance, 'KES');
            });
        } catch (Exception) {
            $ATAirtimeBalance = null;
        }

        try {
            $kyandaFloatBalance = Cache::rememberForever('kyanda_float_balance', function() {
                return KyandaApi::balance()['Account_Bal'];
            });
        } catch (Exception) {
            $kyandaFloatBalance = null;
        }

        return $this->successResponse([
            'tanda_float_balance'  => $tandaFloatBalance,
            'kyanda_float_balance' => $kyandaFloatBalance,
            'at_airtime_balance'   => $ATAirtimeBalance,
        ]);
    }
}
