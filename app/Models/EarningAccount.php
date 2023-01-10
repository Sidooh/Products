<?php

namespace App\Models;

use App\Enums\EarningAccountType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperEarningAccount
 */
class EarningAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'self_amount',
        'invite_amount',
        'type',
    ];

    protected function balance(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['self_amount'] + $attributes['invite_amount']
        );
    }

    public function scopeWithdrawal(Builder $query): Builder
    {
        return $query->whereType(EarningAccountType::WITHDRAWALS->name);
    }

    public function scopeAccountId(Builder $query, int $accountId): Builder
    {
        return $query->whereAccountId($accountId);
    }
}
