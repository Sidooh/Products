<?php

namespace App\Services;

use App\Models\ProductAccount;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SidoohAccounts
{
    private static string $url;

    static function authenticate(): PromiseInterface|Response
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Authenticate...   --- --- --- --- ---');

        $url = config('services.sidooh.services.accounts.url');

        return Http::retry(2)->post("$url/users/signin", [
            'email'    => 'aa@a.a',
            'password' => "12345678"
        ]);
    }

    /**
     * @throws Exception
     */
    static function find($id): array
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Find Account...   --- --- --- --- ---', ['id' => $id]);

        self::$url = config('services.sidooh.services.accounts.url') . "/accounts/$id";

        $acc = Cache::remember($id, (60 * 60 * 24), fn() => self::fetch());

        if(!$acc) throw new Exception("Account doesn't exist!");

        return $acc;
    }

    /**
     * @throws Exception
     */
    static function findPhone($accountId)
    {
        return self::find($accountId)['phone'];
    }

    /**
     * @throws Exception
     */
    static function findByPhone($phone)
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Find Account...   --- --- --- --- ---', ['phone' => $phone]);

        self::$url = config('services.sidooh.services.accounts.url') . "/accounts/phone/$phone";

        $acc = Cache::remember($phone, (60 * 60 * 24), function() {
            $acc = self::fetch();

            Cache::put($acc['id'], $acc);

            return $acc;
        });

        if(!$acc) throw new Exception("Account doesn't exist!");

        return $acc;
    }

    /**
     * @throws Exception
     */
    static function fetch($method = "GET", $data = []): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Fetch Account...   --- --- --- --- ---', [
            'method' => $method,
            "data"   => $data
        ]);

        $authCookie = Cache::remember("accounts_auth_cookie", (60 * 60 * 24), fn() => self::authenticate()->cookies());

        return Http::send($method, self::$url, ['cookies' => $authCookie, 'json' => $data])->throw()->json();
    }

    static function syncUtilityAccounts(int $accountId, string $provider, string $number, $product = 'airtime')
    {
        //        TODO: How and when should we limit and to what number the users churches. Does it affect church user counts?
        $uA = ProductAccount::whereAccountId($accountId);
        if($product === 'utility') $uA = $uA->whereProvider($provider);
        $uA = $uA->latest()->take(3)->get();
        if(count($uA) >= 3) return null;

        $exists = $uA->firstWhere('account_number', $number);

        if($exists) return null;

        return ProductAccount::create([
            'account_id'     => $accountId,
            'account_number' => $number,
            'provider'       => $provider
        ]);
    }
}
