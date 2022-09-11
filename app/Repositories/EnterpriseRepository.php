<?php

namespace App\Repositories;

use App\Models\Enterprise;
use Illuminate\Database\Eloquent\Model;

class EnterpriseRepository
{
    public function store(string $name, string $setting): Enterprise|Model
    {
        return Enterprise::create([
            'accountable_id' => $accountId,
            'accountable_type' => $accountType
        ]);
    }
}
