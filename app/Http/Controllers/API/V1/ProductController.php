<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EventType;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Http\Controllers\Controller;
use App\Services\SidoohNotify;
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

    public function checkProviderBalances(): JsonResponse
    {
        $tanda = TandaApi::balance();
        $kyanda = KyandaApi::balance();
        $AT = AfricasTalkingApi::balance();

        $tandaFloatBalance = $tanda[0]->balances[0]->available;
        $kyandaFloatBalance = $kyanda['Account_Bal'];
        $atBalance = (float) ltrim($AT['data']->UserData->balance, 'KES');

        $message = "! System Balances Below Threshold:\n";

        if ($tandaFloatBalance <= config('services.tanda.float.threshold')) {
            $message .= "\t - Tanda Float: $tandaFloatBalance\n";
        }
        if ($kyandaFloatBalance <= config('services.kyanda.float.threshold')) {
            $message .= "\t - Kyanda Float: $kyandaFloatBalance\n";
        }
        if ($atBalance <= config('services.at.airtime.threshold')) {
            $message .= "\t - At Airtime: $atBalance\n\n";
        }

        $message .= '#SRV:Products';

        if (str($message)->contains('-')) {
            SidoohNotify::notify(admin_contacts(), $message, EventType::STATUS_UPDATE);
        }

        return $this->successResponse([
            'balances'  => ['at' => $atBalance, 'tanda' => $tandaFloatBalance, 'kyanda' => $kyandaFloatBalance],
            'at'        => $AT,
            'tanda'     => $tanda,
            'kyanda'    => $kyanda,
        ]);
    }
}
