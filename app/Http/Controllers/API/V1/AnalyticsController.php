<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\ProductType;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function sla(): JsonResponse
    {
        $sla = Transaction::selectRaw('YEAR(created_at) as year, status, count(*) as count')
                          ->groupByRaw('year, status')
                          ->get();

        return $this->successResponse($sla);
    }

    public function transactions(): JsonResponse
    {
        $data = Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                           ->groupBy('date', 'status')
                           ->orderByDesc('date')
                           ->get();

        return $this->successResponse($data);
    }

    public function revenue(): JsonResponse
    {
        $data = Transaction::selectRaw("status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                           ->whereType(TransactionType::PAYMENT)
                           ->whereNot('product_id', ProductType::VOUCHER)
                           ->groupBy('date', 'status')
                           ->orderByDesc('date')
                           ->get();

        return $this->successResponse($data);
    }

    public function transactionsByTelco(): JsonResponse
    {
        $data = Transaction::selectRaw("destination, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                           ->whereProductId(ProductType::AIRTIME)
                           ->groupBy('date', 'destination', 'status')
                           ->orderByDesc('date')
                           ->get()
                           ->groupBy(fn ($tx) => getTelcoFromPhone($tx->destination));

        return $this->successResponse($data);
    }

    public function revenueByTelco(): JsonResponse
    {
        $data = Transaction::selectRaw("destination, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                           ->whereProductId(ProductType::AIRTIME)
                           ->whereType(TransactionType::PAYMENT)
                           ->groupBy('date', 'destination', 'status')
                           ->orderByDesc('date')
                           ->get()
                           ->groupBy(fn ($tx) => getTelcoFromPhone($tx->destination));

        return $this->successResponse($data);
    }

    public function transactionsByProduct(): JsonResponse
    {
        $data = Transaction::selectRaw("product_id, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, COUNT(*) as count")
                           ->groupBy('date', 'product_id', 'status')
                           ->orderByDesc('date')
                           ->get()
                           ->groupBy(fn ($tx) => ProductType::from($tx->product_id)->name);

        return $this->successResponse($data);
    }

    public function revenueByProduct(): JsonResponse
    {
        $data = Transaction::selectRaw("product_id, status, DATE_FORMAT(created_at, '%Y%m%d%H') as date, SUM(amount) as amount")
                           ->whereType(TransactionType::PAYMENT)
                           ->groupBy('date', 'product_id', 'status')
                           ->orderByDesc('date')
                           ->get()
                           ->groupBy(fn ($tx) => ProductType::from($tx->product_id)->name);

        return $this->successResponse($data);
    }
}
