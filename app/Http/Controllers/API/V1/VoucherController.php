<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class VoucherController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->successResponse(Voucher::all());
    }
}
