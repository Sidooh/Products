<?php

namespace App\Services;

use App\Events\ReferralJoinedEvent;
use App\Repositories\ReferralRepository;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Propaganistas\LaravelPhone\PhoneNumber;
use function env;

class SidoohAccounts
{
    public static function authenticate(): PromiseInterface|Response
    {
        return Http::retry(2)->post(env('SIDOOH_ACCOUNT_URL') . "/users/signin", [
            'email'    => 'aa@a.a',
            'password' => "12345678"
        ]);
    }

    /**
     * @throws Exception
     */
    public static function findOrCreate($phone)
    {
        $phone = ltrim(PhoneNumber::make($phone, 'KE')->formatE164(), '+');

        $acc = self::findByPhone($phone);

        if(!$acc) $acc = self::create($phone);

        return $acc;
    }

    public static function findByPhone($phone): ?array
    {
        Log::info('----------------- Sidooh find Account', ['phone' => $phone,]);

        try {
            $url = env('SIDOOH_ACCOUNT_URL') . "/accounts/phone/$phone";

            $response = self::sendRequest($url, 'GET');

            Log::info('----------------- Sidooh find Account by phone sent', ['id' => $response->json()['id']]);

            return $response->json();
        } catch (Exception $err) {
            Log::error($err);
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public static function create($phone)
    {
        Log::info('----------------- Sidooh create Account', ['phone' => $phone,]);

        $url = env('SIDOOH_ACCOUNT_URL') . "/accounts";

        $response = self::sendRequest($url, 'POST', [
            'phone' => $phone
        ]);

        Log::info('----------------- Sidooh create Account sent', ['id' => $response->json()['id']]);

        return $response->json();
    }

    /**
     * @throws Exception
     */
    public static function sendRequest($url, $method = 'POST', $data = []): Response
    {
        $authCookie = self::authenticate()->cookies();

        return Http::send($method, $url, ['cookies' => $authCookie, 'json' => $data]);
    }
}
