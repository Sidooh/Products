<?php

namespace App\Services;

use App\Models\ProductAccount;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohAccounts extends SidoohService
{
    /**
     * @throws Exception
     */
    static function find(int|string $id): array
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Find Account...   --- --- --- --- ---', ['id' => $id]);

        $url = config('services.sidooh.services.accounts.url') . "/accounts/$id";

        $acc = Cache::remember($id, (60 * 60 * 24), fn() => self::fetch($url));

        if(!$acc) throw new Exception("Account doesn't exist!");

        return $acc;
    }

    /**
     * @throws Exception
     */
    static function findPhone(int|string $accountId)
    {
        return self::find($accountId)['phone'];
    }

    static function fetch(string $url, string $method = "GET", array $data = []): ?array
    {
        Log::info('--- --- --- --- ---   ...[SRV - ACCOUNTS]: Fetch...   --- --- --- --- ---', [
            'method' => $method,
            "data"   => $data
        ]);

        try {
            return parent::http()->send($method, $url, ['json' => $data])->json();
        } catch (Exception $e) {
            Log::error($e);
        }
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
