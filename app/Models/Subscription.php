<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSubscription
 */
class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'start_date',
        'end_date',
        'account_id'
    ];

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function subscriptionType(): BelongsTo
    {
        return $this->belongsTo(SubscriptionType::class);
    }



    /**
     * Scope a query to only include active subscriptions.
     *
     * @param Builder $query
     * @return bool
     */
    public static function active(int $accountId): bool
    {
        return self::whereAccountId($accountId)->whereDate('end_date', '>=', now())->exists();
    }
}
