<?php

namespace App\Services;

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

        $acc = Cache::remember($id, now()->addDay(), fn() => parent::fetch($url));

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
}
