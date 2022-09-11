<?php

namespace App\Repositories;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Model;

class EnterpriseAccountRepository
{
    public function store(Enterprise $enterprise, string $type, int $accountId): Enterprise|Model
    {
        return $enterprise->enterpriseAccounts()->create([
            "type"       => $type,
            "account_id" => $accountId
        ]);
    }
}
