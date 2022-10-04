<?php

namespace App\Repositories;

use App\Enums\EnterpriseAccountType;
use App\Models\Enterprise;

class EnterpriseRepository
{
    public function store(string $name, ?array $settings, int $accountId): Enterprise
    {
        $enterprise = Enterprise::create([
            'name' => $name,
            'settings' => $settings,
        ]);

        $enterprise->enterpriseAccounts()->create([
            "account_id" => $accountId,
            "type"       => EnterpriseAccountType::ADMIN,
            "active"     => true
        ]);

        return $enterprise;
    }
}
