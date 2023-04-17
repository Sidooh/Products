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
use Illuminate\Support\Facades\Cache;

class AnalyticsController extends Controller
{
    public function transactionsSLOs(): JsonResponse
    {
        $slo = Cache::remember('transactions_slo', (3600 * 24 * 7), function() {
            return Transaction::selectRaw('YEAR(created_at) as year, status, count(*) as count')
                              ->groupByRaw('year, status')
                              ->get();
        });

        return $this->successResponse($slo);
    }

    public function productsSLOs(): JsonResponse
    {
        $SLOs = Cache::remember('products_slo', (3600 * 24 * 7), fn () => [
            'tanda'    => TandaRequest::selectRaw('ROUND(COUNT(status)/COUNT(*) * 100) slo')
                                      ->fromRaw("(SELECT CASE WHEN status = '000000' THEN 1 END status FROM tanda_requests WHERE created_at > ?) tanda_requests", now()->subYear())
                                      ->value('slo'),
            'payments' => Payment::selectRaw('ROUND(COUNT(status)/COUNT(*) * 100) slo')
                                 ->fromRaw("(SELECT CASE WHEN status = 'COMPLETED' THEN 1 END status FROM payments) payments")
                                 ->value('slo'),
            'savings'  => SavingsTransaction::selectRaw('ROUND(COUNT(status)/COUNT(*) * 100) slo')
                                            ->fromRaw("(SELECT CASE WHEN status = 'COMPLETED' THEN 1 END status FROM savings_transactions) savings_transactions")
                                            ->value('slo'),
        ]);

        return $this->successResponse($SLOs);
    }

    public function transactions(): JsonResponse
    {
        $data = Cache::remember('transactions', (3600 * 24), function() {
            return Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                              ->groupBy('date', 'status')
                              ->orderByDesc('date')
                              ->get();
        });

        return $this->successResponse($data);
    }

    public function revenue(): JsonResponse
    {
        $data = Cache::remember('revenue', (3600 * 24), function() {
            return Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereType(TransactionType::PAYMENT)
                              ->whereNot('product_id', ProductType::VOUCHER)
                              ->groupBy('date', 'status')
                              ->orderByDesc('date')
                              ->get();
        });

        return $this->successResponse($data);
    }

    public function transactionsByTelco(): JsonResponse
    {
        $data = Cache::remember('transactionsByTelco', (3600 * 24), function() {
            return Transaction::selectRaw("destination, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                              ->whereProductId(ProductType::AIRTIME)
                              ->groupBy('date', 'destination', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => getTelcoFromPhone($tx->destination) ?? 'UNKNOWN');
        });

        return $this->successResponse($data);
    }

    public function revenueByTelco(): JsonResponse
    {
        $data = Cache::remember('revenueByTelco', (3600 * 24), function() {
            return Transaction::selectRaw("destination, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereProductId(ProductType::AIRTIME)
                              ->whereType(TransactionType::PAYMENT)
                              ->groupBy('date', 'destination', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => getTelcoFromPhone($tx->destination) ?? 'UNKNOWN');
        });

        return $this->successResponse($data);
    }

    public function transactionsByProduct(): JsonResponse
    {
        $data = Cache::remember('transactionsByProduct', (3600 * 24), function() {
            return Transaction::selectRaw("product_id, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                              ->groupBy('date', 'product_id', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => ProductType::from($tx->product_id)->name);
        });

        return $this->successResponse($data);
    }

    public function revenueByProduct(): JsonResponse
    {
        $data = Cache::remember('revenueByProduct', (3600 * 24), function() {
            return Transaction::selectRaw("product_id, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                              ->whereType(TransactionType::PAYMENT)
                              ->groupBy('date', 'product_id', 'status')
                              ->orderByDesc('date')
                              ->get()
                              ->groupBy(fn ($tx) => ProductType::from($tx->product_id)->name);
        });

        return $this->successResponse($data);
    }
}
