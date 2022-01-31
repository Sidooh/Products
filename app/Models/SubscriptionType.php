<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperSubscriptionType
 */
class SubscriptionType extends Model
{
    use HasFactory;

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function subscription(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
