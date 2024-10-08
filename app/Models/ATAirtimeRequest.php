<?php

namespace App\Models;

use Database\Factories\AirtimeRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * App\Models\ATAirtimeRequest
 *
 * @property int $id
 * @property string $message
 * @property int $num_sent
 * @property string $amount
 * @property string $discount
 * @property string $description
 * @property int|null $transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static AirtimeRequestFactory factory(...$parameters)
 * @method static Builder|ATAirtimeRequest newModelQuery()
 * @method static Builder|ATAirtimeRequest newQuery()
 * @method static Builder|ATAirtimeRequest query()
 * @method static Builder|ATAirtimeRequest whereAmount($value)
 * @method static Builder|ATAirtimeRequest whereCreatedAt($value)
 * @method static Builder|ATAirtimeRequest whereDescription($value)
 * @method static Builder|ATAirtimeRequest whereDiscount($value)
 * @method static Builder|ATAirtimeRequest whereId($value)
 * @method static Builder|ATAirtimeRequest whereMessage($value)
 * @method static Builder|ATAirtimeRequest whereNumSent($value)
 * @method static Builder|ATAirtimeRequest whereTransactionId($value)
 * @method static Builder|ATAirtimeRequest whereUpdatedAt($value)
 *
 * @property-read Collection|ATAirtimeResponse[] $airtimeResponses
 * @property-read int|null $airtime_responses_count
 * @property-read Transaction|null $transaction
 *
 * @mixin IdeHelperATAirtimeRequest
 */
class ATAirtimeRequest extends Model
{
    use HasFactory;

    protected $table = 'at_airtime_requests';

    protected $fillable = [
        'message',
        'num_sent',
        'amount',
        'discount',
        'description',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(ATAirtimeResponse::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(ATAirtimeResponse::class);
    }
}
