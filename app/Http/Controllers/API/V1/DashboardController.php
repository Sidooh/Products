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
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function index(): JsonResponse
    {
        $totalTransactions = Cache::remember('total_transactions', 60 * 60 * 24, fn () => Transaction::count());
        $totalTransactionsToday = Cache::remember('total_transactions_today',
            60 * 60,
            fn () => Transaction::whereDate('created_at', Carbon::today())->count());

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

    public function getChartData(): JsonResponse
    {
        $transactions = Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                                   ->whereType(TransactionType::PAYMENT)
                                   ->groupBy('date', 'status')
                                   ->orderByDesc('date')
                                   ->get();

        return $this->successResponse($transactions);
    }

    public function getProviderBalances(): JsonResponse
    {
        try {
            $tandaFloatBalance = TandaApi::balance()[0]->balances[0]->available;
        } catch (Exception) {
            $tandaFloatBalance = null;
        }

        try {
            $ATAirtimeBalance = (float) ltrim(AfricasTalkingApi::balance()['data']->UserData->balance, 'KES');
        } catch (Exception) {
            $ATAirtimeBalance = null;
        }

        try {
            $kyandaFloatBalance = KyandaApi::balance()['Account_Bal'];
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
