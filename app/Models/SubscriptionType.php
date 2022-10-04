<?php

namespace App\Models;

use Database\Factories\SubscriptionTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\SubscriptionType
 *
 * @property int                                                          $id
 * @property string                                                       $title
 * @property string                                                       $price
 * @property int                                                          $level_limit
 * @property int                                                          $duration
 * @property int                                                          $active
 * @property Carbon|null                              $created_at
 * @property Carbon|null                              $updated_at
 * @property-read Collection|Subscription[] $subscription
 * @property-read int|null                                                $subscription_count
 * @method static SubscriptionTypeFactory factory(...$parameters)
 * @method static Builder|SubscriptionType newModelQuery()
 * @method static Builder|SubscriptionType newQuery()
 * @method static Builder|SubscriptionType query()
 * @method static Builder|SubscriptionType whereActive($value)
 * @method static Builder|SubscriptionType whereCreatedAt($value)
 * @method static Builder|SubscriptionType whereDuration($value)
 * @method static Builder|SubscriptionType whereId($value)
 * @method static Builder|SubscriptionType whereLevelLimit($value)
 * @method static Builder|SubscriptionType wherePrice($value)
 * @method static Builder|SubscriptionType whereTitle($value)
 * @method static Builder|SubscriptionType whereUpdatedAt($value)
 * @mixin IdeHelperSubscriptionType
 */
class SubscriptionType extends Model
{
    use HasFactory;

    protected $casts = [
        'price'  => 'int',
        'active' => 'bool',
    ];

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function subscription(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
