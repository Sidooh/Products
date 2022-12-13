<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SidoohAccounts extends SidoohService
{
    /**
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public static function getAll(): array
    {
        Log::info('...[SRV - ACCOUNTS]: Get All...');

        $url = config('services.sidooh.services.accounts.url').'/accounts?with_user=true';

        //TODO: Process accounts to store with id for ease of fetching even one.
        return Cache::remember('all_accounts', (60 * 60 * 24), fn() => parent::fetch($url));
    }

    /**
     * @throws Exception
     */
    public static function find(int|string $id): array
    {
        Log::info('...[SRV - ACCOUNTS]: Find...', ['id' => $id]);

        $url = config('services.sidooh.services.accounts.url')."/accounts/$id?with_user=true";

        $acc = Cache::remember($id, (60 * 60 * 24), fn() => parent::fetch($url));

        if (! $acc) {
            throw new Exception("Account doesn't exist!");
        }

        return $acc;
    }

    /**
     * @throws Exception
     */
    public static function findByPhone(int|string $phone)
    {
        Log::info('...[SRV - ACCOUNTS]: Find By Phone...', ['phone' => $phone]);

        $url = config('services.sidooh.services.accounts.url')."/accounts/phone/$phone";

        $acc = Cache::remember($phone, (60 * 60 * 24), fn() => parent::fetch($url));

        if (! $acc) {
            throw new Exception("Account doesn't exist!");
        }

        return $acc;
    }

    /**
     * @throws Exception
     */
    public static function findPhone(int|string $accountId)
    {
        return self::find($accountId)['phone'];
    }

    /**
     * @throws Exception
     */
    public static function getInviters(int|string $id): array
    {
        Log::info('...[SRV - ACCOUNTS]: Find Ancestors...', ['id' => $id]);

        $url = config('services.sidooh.services.accounts.url')."/accounts/$id/ancestors";

        $ancestors = Cache::remember("{$id}_ancestors", (60 * 60 * 24 * 28), fn() => parent::fetch($url));

        if (! $ancestors) {
            throw new Exception("Account Ancestors don't exist!");
        }

        return $ancestors;
    }
}
