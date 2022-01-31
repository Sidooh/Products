<?php

namespace App\Services;

use App\Events\ReferralJoinedEvent;
use App\Repositories\ReferralRepository;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
    public static function find($id): array
    {
        $acc = self::fetch($id);

        if(!$acc) throw new Exception("Account doesn't exist!");

        return $acc;
    }

    public static function fetch($id): ?array
    {
        Log::info('----------------- Sidooh find Account', ['id' => $id]);

        try {
            $url = env('SIDOOH_ACCOUNT_URL') . "/accounts/$id";

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
    public static function sendRequest($url, $method = 'POST', $data = []): Response
    {
        $authCookie = self::authenticate()->cookies();

        return Http::send($method, $url, ['cookies' => $authCookie, 'json' => $data]);
    }
}
