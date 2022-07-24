<?php

namespace App\Models;

use App\Enums\Status;
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
        'status',
        'start_date',
        'end_date',
        'account_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
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
     * @param int $accountId
     * @return bool
     */
    public static function active(int $accountId): bool
    {
        return self::whereAccountId($accountId)
//            ->whereDate('start_date', '<', now())
            ->whereDate('end_date', '>', now())
            ->whereStatus(Status::ACTIVE)
            ->exists();
    }

    /**
     * Scope a query to only include almost Expired subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public static function scopeIncludePreExpiry(Builder $query): Builder
    {
        return $query
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(5)->toDateString()])
            ->whereStatus(Status::ACTIVE);
    }

    /**
     * Scope a query to only include almost Expired subscriptions.
     *
     * @param Builder $query
     * @return Builder
     */
    public static function scopeIncludePostExpiry(Builder $query): Builder
    {
        return $query
            ->whereBetween('end_date', [now()->subDays(6)->toDateString(), now()->toDateString()])/*->whereStatus(Status::EXPIRED)*/ ;
    }

    // TODO: This doesn't seem to be used anywhere ???...
    public static function getMany(array $ids)
    {
        return self::whereIn('account_id', $ids)->get();
    }
}
