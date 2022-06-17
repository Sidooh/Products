<?php

namespace App\Repositories;

use App\Enums\ProductType;
use App\Models\AirtimeAccount;
use App\Models\UtilityAccount;
use Illuminate\Database\Eloquent\Model;
use Nabcellent\Kyanda\Library\Providers;

class ProductRepository
{
    static function syncAccounts(array $account, string $provider, string $number): Model|UtilityAccount|AirtimeAccount|null
    {
        $product = match ($provider) {
            Providers::SAFARICOM, Providers::AIRTEL, Providers::FAIBA, Providers::EQUITEL, Providers::TELKOM => ProductType::AIRTIME,
            default => ProductType::UTILITY
        };

        $model = $product === ProductType::UTILITY
            ? new UtilityAccount
            : new AirtimeAccount;

        if($number === $account["phone"]) return null;

        $uA = $model->whereAccountId($account["id"])->whereProvider($provider);
        $exists = $uA->firstWhere('account_number', $number);

        if($exists) return null;

        return $model->create([
            'account_id'     => $account["id"],
            'account_number' => $number,
            'provider'       => $provider
        ]);
    }
}
