<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\Frequency;
use App\Enums\Period;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\ChartAid;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use LocalCarbon;

class DashboardController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(): JsonResponse
    {
        $transactions = Transaction::whereStatus(Status::COMPLETED)->whereType(TransactionType::PAYMENT)->get();

        $totalRevenue = $transactions->sum("amount");
        $totalRevenueToday = $transactions->filter(fn($item) => $item->created_at->isToday())->sum("amount");

        $transactions = Transaction::whereType(TransactionType::PAYMENT)->select([
            "id",
            "description",
            "destination",
            "account_id",
            "product_id",
            "amount",
            "status",
            "updated_at"
        ])->latest()->with(["product:id,name", "payment:id,payment_id,transaction_id,status"])->get();

        $accounts = collect(SidoohAccounts::getAll());
        $transactions->transform(function(Transaction $transaction) use ($accounts) {
            $transaction->account = $accounts->firstWhere("id", $transaction->account_id);
            return $transaction;
        });

        return response()->json([
            "total_today"     => Transaction::whereStatus(Status::COMPLETED)->whereDate("created_at", Carbon::today())
                ->sum("amount"),
            "total_yesterday" => Transaction::whereStatus(Status::COMPLETED)
                ->whereDate("created_at", Carbon::yesterday())->sum("amount"),

            "total_transactions"       => Transaction::count(),
            "total_transactions_today" => Transaction::whereDate("created_at", Carbon::today())->count(),

            "total_revenue_today" => $totalRevenueToday,
            "total_revenue"       => $totalRevenue,

            "recent_transactions"  => $transactions->take(70),
            "pending_transactions" => $transactions->filter(fn(Transaction $transaction) => $transaction->status === Status::PENDING->value)
                ->values()
        ]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        $frequency = Frequency::tryFrom((string)$request->input("frequency")) ?? Frequency::HOURLY;

        $chartAid = new ChartAid(Period::TODAY, $frequency, "sum", "amount");
        $chartAid->setShowFuture(true);

        $fetch = function(array $whereBetween, int $freqCount = null) use ($chartAid) {
            $transactions = Transaction::select(["status", "created_at", "amount"])
                ->whereBetween('created_at', $whereBetween)->get();

            $transform = function($transactions, $key) use ($freqCount, $chartAid) {
                $models = $transactions->groupBy(fn($item) => $chartAid->chartDateFormat($item->created_at));

                return [$key => $chartAid->chartDataSet($models, $freqCount)];
            };

            return $transactions->groupBy("status")->toBase()->mapWithKeys($transform)
                ->merge($transform($transactions, "ALL"));
        };

        $todayHrs = LocalCarbon::now()->diffInHours(LocalCarbon::now()->startOfDay());

        return response()->json([
            "today"     => $fetch([
                LocalCarbon::today()->startOfDay()->utc(),
                LocalCarbon::today()->endOfDay()->utc()
            ], $todayHrs + 1),
            "yesterday" => $fetch([
                LocalCarbon::yesterday()->startOfDay()->utc(),
                LocalCarbon::yesterday()->endOfDay()->utc()
            ]),
        ]);
    }
}
