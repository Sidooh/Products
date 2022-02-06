<?php

namespace App\Models;

use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Subscription
 *
 * @property int                             $id
 * @property string                          $amount
 * @property string                          $start_date
 * @property string                          $end_date
 * @property string                          $status
 * @property int                             $account_id
 * @property int                             $subscription_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubscriptionType           $subscriptionType
 * @method static SubscriptionFactory factory(...$parameters)
 * @method static Builder|Subscription newModelQuery()
 * @method static Builder|Subscription newQuery()
 * @method static Builder|Subscription query()
 * @method static Builder|Subscription whereAccountId($value)
 * @method static Builder|Subscription whereAmount($value)
 * @method static Builder|Subscription whereCreatedAt($value)
 * @method static Builder|Subscription whereEndDate($value)
 * @method static Builder|Subscription whereId($value)
 * @method static Builder|Subscription whereStartDate($value)
 * @method static Builder|Subscription whereStatus($value)
 * @method static Builder|Subscription whereSubscriptionTypeId($value)
 * @method static Builder|Subscription whereUpdatedAt($value)
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
