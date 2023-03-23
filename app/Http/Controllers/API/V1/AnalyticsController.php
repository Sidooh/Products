<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function sla(): JsonResponse
    {
        $sla = Transaction::selectRaw('YEAR(created_at) as year, status, count(*) as count')->groupByRaw('year, status')->get();

        return $this->successResponse($sla);
    }
}
