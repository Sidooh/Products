<?php

namespace App\Repositories;

use App\Models\AirtimeAccount;
use App\Models\UtilityAccount;
use DrH\Mpesa\Exceptions\MpesaException;
use Nabcellent\Kyanda\Library\Providers;

class ProductRepository
{
    static function syncAccounts(array $account, string $provider, string $number)
    {
        $product = match ($provider) {
            Providers::SAFARICOM, Providers::AIRTEL, Providers::FAIBA, Providers::EQUITEL, Providers::TELKOM => "airtime",
            default => "utility"
        };

        $model = $product === 'utility'
            ? new UtilityAccount()
            : new AirtimeAccount();

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
