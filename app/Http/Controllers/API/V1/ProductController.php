<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\EventType;
use App\Helpers\AfricasTalking\AfricasTalkingApi;
use App\Helpers\Kyanda\KyandaApi;
use App\Helpers\Tanda\TandaApi;
use App\Http\Controllers\Controller;
use App\Services\SidoohNotify;
use Exception;
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

    public function queryProviderBalances(): void
    {
        $message = "Provider Balances:\n";

        try {
            $tanda = TandaApi::balance();
            $tandaFloatBalance = $tanda[0]->balances[0]->available;
            $tandaIsBelowThresh = $tandaFloatBalance <= config('services.tanda.float.threshold');
        } catch (Exception) {
            $tandaIsBelowThresh = true;
            $tandaFloatBalance = 'Error';
        }
        /*try {
            $kyanda = KyandaApi::balance();
            $kyandaFloatBalance = $kyanda['Account_Bal'];
            $kyandaIsBelowThresh = $kyandaFloatBalance <= config('services.kyanda.float.threshold');
        } catch (Exception) {
            $kyandaIsBelowThresh = true;
            $kyandaFloatBalance = 'Error';
        }*/
        /*try {
            $AT = AfricasTalkingApi::balance();
            $ATAirtimeBalance = (float) ltrim($AT['data']->UserData->balance, 'KES');
            $ATAirtimeIsBelowThresh = $ATAirtimeBalance <= config('services.at.airtime.threshold');
        } catch (Exception) {
            $ATAirtimeIsBelowThresh = true;
            $ATAirtimeBalance = 'Error';
        }*/

        if ($tandaIsBelowThresh) {
            $message .= "\t - Tanda Float: $tandaFloatBalance\n";
//            $message .= "\t - Kyanda Float: $kyandaFloatBalance\n";
//            $message .= "\t - At Airtime: $ATAirtimeBalance\n";

            $message .= "\n#SRV:Products";

            SidoohNotify::notify(admin_contacts(), $message, EventType::STATUS_UPDATE);
        }
    }
}
