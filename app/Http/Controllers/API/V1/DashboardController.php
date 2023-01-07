<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Frequency;
use App\Enums\Period;
use App\Enums\ProductType;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\ChartAid;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use LocalCarbon;

class DashboardController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(): JsonResponse
    {
        $totalTransactions = Cache::remember('total_transactions', 60 * 60 * 24, function() {
            return Transaction::count();
        });
        $totalTransactionsToday = Cache::remember('total_transactions_today', 60 * 60, function() {
            return Transaction::whereDate('created_at', Carbon::today())->count();
        });

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

            'total_revenue'            => $totalRevenue,
            'total_revenue_today'      => $totalRevenueToday,
        ]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        $frequency = Frequency::tryFrom((string) $request->input('frequency')) ?? Frequency::HOURLY;

        $chartAid = new ChartAid(Period::TODAY, $frequency, 'sum', 'amount');
        $chartAid->setShowFuture(true);

        $fetch = function(array $whereBetween, int $freqCount = null) use ($chartAid) {
            $cacheKey = 'transactions_'.implode('_', $whereBetween);
            $transactions = Cache::remember($cacheKey, 60 * 60, function() use ($whereBetween) {
                return Transaction::select(['status', 'created_at', 'amount'])
                    ->whereBetween('created_at', $whereBetween)->get();
            });

            $transform = function($transactions, $key) use ($freqCount, $chartAid) {
                $models = $transactions->groupBy(fn ($item) => $chartAid->chartDateFormat($item->created_at));

                return [$key => $chartAid->chartDataSet($models, $freqCount)];
            };

            return $transactions->groupBy('status')->toBase()->mapWithKeys($transform)
                ->merge($transform($transactions, 'ALL'));
        };

        $todayHrs = LocalCarbon::now()->diffInHours(LocalCarbon::now()->startOfDay());

        return response()->json([
            'today'     => $fetch([
                LocalCarbon::today()->startOfDay()->utc(),
                LocalCarbon::today()->endOfDay()->utc(),
            ], $todayHrs + 1),
            'yesterday' => $fetch([
                LocalCarbon::yesterday()->startOfDay()->utc(),
                LocalCarbon::yesterday()->endOfDay()->utc(),
            ]),
        ]);
    }
}
