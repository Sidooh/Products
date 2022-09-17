<?php

namespace App\Repositories;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Model;

class EnterpriseAccountRepository
{
    public function store(Enterprise $enterprise, string $type, int $accountId): Enterprise|Model
    {
        $enterpriseAccount = $enterprise->enterpriseAccounts()->create([
            "type"       => $type,
            "account_id" => $accountId
        ]);

//        $enterpriseAccount->float_account = SidoohPayments::createFloatAccount(Initiator::ENTERPRISE, $enterprise->id);

        return $enterpriseAccount;
    }
}
