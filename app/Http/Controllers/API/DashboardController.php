<?php

namespace App\Http\Controllers\API;

use App\Enums\Frequency;
use App\Enums\Period;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Helpers\ChartAid;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
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
        ])->latest()->with("product:id,name")->get();

        $accounts = collect(SidoohAccounts::getAll());
        $transactions->transform(function(Transaction $transaction) use ($accounts) {
            $transaction->account = $accounts->firstWhere("id", $transaction->account_id);
            return $transaction;
        });

        $payments = collect(SidoohPayments::getAll());
        $transactions->transform(function(Transaction $transaction) use ($payments) {
            $transaction->payment = $payments->firstWhere("payable_id", $transaction->id);
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
            "pending_transactions" => $transactions->filter(fn(Transaction $transaction) => $transaction->status === Status::PENDING->value)->values()
        ]);
    }

    public function revenueChart(Request $request): JsonResponse
    {
        $frequency = Frequency::tryFrom((string)$request->input('frequency')) ?? Frequency::HOURLY;
        $status = $request->input('paymentStatus', Status::COMPLETED);

        $whereStatus = $status === "ALL" ? Status::cases() : [$status];

        $chartAid = new ChartAid(Period::TODAY, $frequency, 'sum', 'amount');
        $chartAid->setShowFuture(true);

        $transactionsYesterday = Transaction::select(['created_at', 'amount'])->whereBetween('created_at', [
            LocalCarbon::yesterday()->startOfDay()->utc(),
            LocalCarbon::yesterday()->endOfDay()->utc()
        ])->whereIn('status', $whereStatus)->get()->groupBy(function($item) use ($chartAid) {
            return $chartAid->chartDateFormat($item->created_at);
        });

        $transactionsToday = Transaction::select(['created_at', 'amount'])->whereBetween('created_at', [
            LocalCarbon::today()->startOfDay()->utc(),
            LocalCarbon::today()->endOfDay()->utc()
        ])->whereIn('status', $whereStatus)->get()->groupBy(function($item) use ($chartAid) {
            return $chartAid->chartDateFormat($item->created_at);
        });

        $todayHrs = LocalCarbon::now()->diffInHours(LocalCarbon::now()->startOfDay());

        return response()->json([
            "yesterday" => $chartAid->chartDataSet($transactionsYesterday),
            "today"     => $chartAid->chartDataSet($transactionsToday, $todayHrs + 1),
        ]);
    }
}
