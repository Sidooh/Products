<?php

namespace App\Models;

use Database\Factories\AirtimeResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ATAirtimeResponse
 *
 * @property int $id
 * @property string $phone
 * @property string $message
 * @property string $amount
 * @property string $status
 * @property string $request_id
 * @property string $discount
 * @property int $airtime_request_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static AirtimeResponseFactory factory(...$parameters)
 * @method static Builder|ATAirtimeResponse newModelQuery()
 * @method static Builder|ATAirtimeResponse newQuery()
 * @method static Builder|ATAirtimeResponse query()
 * @method static Builder|ATAirtimeResponse whereAirtimeRequestId($value)
 * @method static Builder|ATAirtimeResponse whereAmount($value)
 * @method static Builder|ATAirtimeResponse whereCreatedAt($value)
 * @method static Builder|ATAirtimeResponse whereDiscount($value)
 * @method static Builder|ATAirtimeResponse whereId($value)
 * @method static Builder|ATAirtimeResponse whereMessage($value)
 * @method static Builder|ATAirtimeResponse wherePhone($value)
 * @method static Builder|ATAirtimeResponse whereRequestId($value)
 * @method static Builder|ATAirtimeResponse whereStatus($value)
 * @method static Builder|ATAirtimeResponse whereUpdatedAt($value)
 * @property string|null $description
 * @property-read \App\Models\ATAirtimeRequest $airtimeRequest
 * @method static Builder|ATAirtimeResponse whereDescription($value)
 * @mixin IdeHelperATAirtimeResponse
 */
class ATAirtimeResponse extends Model
{
    use HasFactory;

    protected $table = 'at_airtime_responses';

    protected $fillable = [
        'phone',
        'message',
        'amount',
        'discount',
        'request_id',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ATAirtimeRequest::class);
    }
}
