<?php

namespace App\Services;

use App\Events\ReferralJoinedEvent;
use App\Repositories\ReferralRepository;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
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
        $acc = Cache::get($id, function() use ($id) {
            $acc = self::fetch($id);

            Cache::put($acc['id'], $acc);

            return $acc;
        });

        if(!$acc) throw new Exception("Account doesn't exist!");

        return $acc;
    }

    /**
     * @throws Exception
     */
    public static function fetch($id): ?array
    {
        Log::info('----------------- Sidooh find Account', ['id' => $id]);

        $url = env('SIDOOH_ACCOUNT_URL') . "/accounts/$id";

        $response = self::sendRequest($url, 'GET');

        Log::info('----------------- Sidooh find Account by phone sent', ['id' => $response->json()['id']]);

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
