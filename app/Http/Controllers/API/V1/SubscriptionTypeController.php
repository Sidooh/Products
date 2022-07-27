<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionType;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionTypeController extends Controller
{
    /**
     * @throws Exception|\Throwable
     */
    public function __invoke(Request $request): JsonResponse
    {
        $subscriptionType = SubscriptionType::first();

        return $this->successResponse($subscriptionType);
    }
}
