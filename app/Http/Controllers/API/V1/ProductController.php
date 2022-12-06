<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Tanda\TandaApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use function mb_strtolower;

class ProductController extends Controller
{
    public function getEarningRates(): JsonResponse
    {
        $provider = mb_strtolower(config('services.sidooh.utilities_provider'));
        $discounts = config("services.$provider.discounts");

        return $this->successResponse($discounts);
    }

    public function getServiceProviderBalance(): JsonResponse
    {
        return $this->successResponse([
            'tanda' => TandaApi::balance(),
            'at'    => AfricasTalkingApi::balance(),
        ]);
    }
}
