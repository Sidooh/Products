<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SavingsTransaction;
use App\Models\Transaction;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function transactionsSLO(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('transactions_slo');
        }

        $slo = Cache::remember('transactions_slo', (3600 * 24 * 7), function() {
            return Transaction::selectRaw('YEAR(created_at) as year, status, count(*) as count')
                              ->groupByRaw('year, status')
                              ->get();
        });

        return $this->successResponse($slo);
    }

    public function productsSLO(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('products_slo');
        }

        $SLO = Cache::remember('products_slo', (3600 * 24 * 7), function() {
            return Transaction::selectRaw('product_id, year, COUNT(status)/COUNT(*) * 100 slo')
                              ->fromRaw("(SELECT product_id, YEAR(created_at) as year, CASE WHEN status = 'COMPLETED' THEN 1 END status FROM transactions) transactions")
                              ->groupBy('year', 'product_id')
                              ->get()->map(fn ($tx) => [
                                  'year'    => $tx->year,
                                  'slo'     => $tx->slo,
                                  'product' => ProductType::from($tx->product_id)->name,
                              ]);
        });

        return $this->successResponse($SLO);
    }

    public function vendorsSLO(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('vendors_slo');
        }

        $SLO = Cache::remember('vendors_slo', (3600 * 24 * 7), fn () => [
            'tanda'    => TandaRequest::selectRaw('COUNT(status)/COUNT(*) * 100 slo')
                                      ->fromRaw("(SELECT CASE WHEN status = '000000' THEN 1 END status FROM tanda_requests WHERE created_at > ?) tanda_requests",
                                          now()->subYear())
                                      ->value('slo'),
            'payments' => Payment::selectRaw('COUNT(status)/COUNT(*) * 100 slo')
                                 ->fromRaw("(SELECT CASE WHEN status = 'COMPLETED' THEN 1 END status FROM payments) payments")
                                 ->value('slo'),
            'savings'  => SavingsTransaction::selectRaw('COUNT(status)/COUNT(*) * 100 slo')
                                            ->fromRaw("(SELECT CASE WHEN status = 'COMPLETED' THEN 1 END status FROM savings_transactions) savings_transactions")
                                            ->value('slo'),
        ]);

        return $this->successResponse($SLO);
    }

    public function transactions(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('transactions_count_analytics');
        }

        $data = Cache::remember('transactions_count_analytics', (3600 * 24), function() {
            return Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                              ->whereDate('created_at', '>', now()->subYear())
                              ->groupBy('date', 'status')
                              ->orderByDesc('date')
                              ->get();
        });

        return $this->successResponse($data);
    }

    public function revenue(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('revenue_count_analytics');
        }

        $data = Cache::remember('revenue_count_analytics', (3600 * 24), function() {
            return Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereType(TransactionType::PAYMENT)
                              ->whereNot('product_id', ProductType::VOUCHER)
                              ->whereDate('created_at', '>', now()->subYear())
                              ->groupBy('date', 'status')
                              ->orderByDesc('date')
                              ->get();
        });

        return $this->successResponse($data);
    }

    public function transactionsByTelco(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('transactions_by_Telco');
        }

        $data = Cache::remember('transactions_by_Telco', (3600 * 24), function() {
            return Transaction::selectRaw("destination, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                              ->whereProductId(ProductType::AIRTIME)
                              ->whereDate('created_at', '>', now()->subYear())
                              ->groupBy('date', 'destination', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => getTelcoFromPhone((int) $tx->destination) ?? 'UNKNOWN');
        });

        return $this->successResponse($data);
    }

    public function revenueByTelco(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('revenue_by_Telco');
        }

        $data = Cache::remember('revenue_by_Telco', (3600 * 24), function() {
            return Transaction::selectRaw("destination, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereProductId(ProductType::AIRTIME)
                              ->whereType(TransactionType::PAYMENT)
                              ->whereDate('created_at', '>', now()->subYear())
                              ->groupBy('date', 'destination', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => getTelcoFromPhone((int) $tx->destination) ?? 'UNKNOWN');
        });

        return $this->successResponse($data);
    }

    public function transactionsByProduct(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('transactions_by_Product');
        }

        $data = Cache::remember('transactions_by_Product', (3600 * 24), function() {
            return Transaction::selectRaw("product_id, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                              ->whereDate('created_at', '>', now()->subYear())
                              ->groupBy('date', 'product_id', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => ProductType::from($tx->product_id)->name);
        });

        return $this->successResponse($data);
    }

    public function revenueByProduct(Request $request): JsonResponse
    {
        if ($request->query('bypass_cache') === 'true') {
            Cache::forget('revenue_by_Product');
        }

        $data = Cache::remember('revenue_by_Product', (3600 * 24), function() {
            return Transaction::selectRaw("product_id, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereType(TransactionType::PAYMENT)
                              ->whereNotIn('product_id', [
                                  ProductType::VOUCHER,
                                  ProductType::WITHDRAWAL,
                                  ProductType::FLOAT,
                              ])
                              ->whereDate('created_at', '>', now()->subYear())
                              ->groupBy('date', 'product_id', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => ProductType::from($tx->product_id)->name);
        });

        return $this->successResponse($data);
    }
}
