<?php

namespace App\Http\Controllers\API;

use App\Enums\Status;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohPayments;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

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
            "account_id",
            "amount",
            "status",
            "updated_at"
        ])->latest()->get();

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
            "pending_transactions" => $transactions->filter(fn(Transaction $transaction) => $transaction->status === Status::PENDING->value)
        ]);
    }
}
