<?php

namespace App\Repositories;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Model;

class EnterpriseRepository
{
    public function store(string $name, ?array $settings, ?array $accounts): Enterprise|Model
    {
        $enterprise = Enterprise::create([
            'name'     => $name,
            'settings' => $settings,
        ]);

        if (isset($accounts) && count($accounts) > 0) {
            $enterprise->enterpriseAccounts()->createMany($accounts);
        }

        return $enterprise;
    }
}
