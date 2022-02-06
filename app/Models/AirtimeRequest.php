<?php

namespace App\Models;

use Database\Factories\AirtimeRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\AirtimeRequest
 *
 * @property int                             $id
 * @property string                          $message
 * @property int                             $num_sent
 * @property string                          $amount
 * @property string                          $discount
 * @property string                          $description
 * @property int|null                        $transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static AirtimeRequestFactory factory(...$parameters)
 * @method static Builder|AirtimeRequest newModelQuery()
 * @method static Builder|AirtimeRequest newQuery()
 * @method static Builder|AirtimeRequest query()
 * @method static Builder|AirtimeRequest whereAmount($value)
 * @method static Builder|AirtimeRequest whereCreatedAt($value)
 * @method static Builder|AirtimeRequest whereDescription($value)
 * @method static Builder|AirtimeRequest whereDiscount($value)
 * @method static Builder|AirtimeRequest whereId($value)
 * @method static Builder|AirtimeRequest whereMessage($value)
 * @method static Builder|AirtimeRequest whereNumSent($value)
 * @method static Builder|AirtimeRequest whereTransactionId($value)
 * @method static Builder|AirtimeRequest whereUpdatedAt($value)
 * @mixin IdeHelperAirtimeRequest
 */
class AirtimeRequest extends Model
{
    use HasFactory;

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

    public function airtimeResponses(): HasMany
    {
        return $this->hasMany(AirtimeResponse::class);
    }
}
