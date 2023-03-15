<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SidoohService
{
    // TODO: implement Sidooh Services LIB for php, to be used in Prod/Pay
    public static function http(): PendingRequest
    {
        $token = Cache::remember('auth_token', (60 * 14), fn () => self::authenticate());

        return Http::timeout(20)->withToken($token)->acceptJson();
    }

    /**
     * @throws RequestException
     */
    public static function authenticate(): string
    {
        Log::info('...[SRV - SIDOOH]: AUTH...');

        $url = config('services.sidooh.services.accounts.url');

        $response = Http::post("$url/users/signin", [
            'email'    => 'aa@a.a',
            'password' => '12345678',
        ]);

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        return $response->throw()->json();
    }

    /**
     * @throws Exception
     */
    public static function fetch(string $url, string $method = 'GET', array $data = [])
    {
        Log::info('...[SRV - SIDOOH]: REQ...', [
            'url'    => $url,
            'method' => $method,
            'data'   => $data,
        ]);

        $options = strtoupper($method) === 'POST' ? ['json' => $data] : [];

        $t = microtime(true);
        try {
            $response = self::http()->send($method, $url, $options)->throw()->json();
            $latency = round((microtime(true) - $t) * 1000, 2);

            Log::info('...[SRV - SIDOOH]: RES... '.$latency.'ms', [$response]);

            return $response['data'];
        } catch (Exception|RequestException $err) {
            $latency = round((microtime(true) - $t) * 1000, 2);

            if ($err->getCode() === 401) {
                Log::error('...[SRV - SIDOOH]: ERR... '.$latency.'ms', $err->response->json());
                throw new Exception('Something went wrong, please try again later.');
            }

            if (str_starts_with($err->getCode(), 4)) {
                throw new HttpResponseException(response()->json($err->response->json(), $err->getCode()));
            }

            Log::critical('...[SRV - SIDOOH]: ERR... '.$latency.'ms', [$err]);
            throw new Exception('Something went wrong, please try again later.');
        }
    }
}
