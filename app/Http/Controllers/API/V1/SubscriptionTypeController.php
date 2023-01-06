<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $subTypes = SubscriptionType::select([
            'id',
            'title',
            'price',
            'level_limit',
            'duration',
            'active',
            'period',
        ])->latest()->get();

        return $this->successResponse($subTypes);
    }

    public function defaultSubscriptionType(Request $request): JsonResponse
    {
        $subscriptionType = SubscriptionType::first();

        return $this->successResponse($subscriptionType);
    }
}
