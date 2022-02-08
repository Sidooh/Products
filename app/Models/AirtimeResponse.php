<?php

namespace App\Models;

use Database\Factories\AirtimeResponseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\AirtimeResponse
 *
 * @property int         $id
 * @property string      $phone
 * @property string      $message
 * @property string      $amount
 * @property string      $status
 * @property string      $request_id
 * @property string      $discount
 * @property int         $airtime_request_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static AirtimeResponseFactory factory(...$parameters)
 * @method static Builder|AirtimeResponse newModelQuery()
 * @method static Builder|AirtimeResponse newQuery()
 * @method static Builder|AirtimeResponse query()
 * @method static Builder|AirtimeResponse whereAirtimeRequestId($value)
 * @method static Builder|AirtimeResponse whereAmount($value)
 * @method static Builder|AirtimeResponse whereCreatedAt($value)
 * @method static Builder|AirtimeResponse whereDiscount($value)
 * @method static Builder|AirtimeResponse whereId($value)
 * @method static Builder|AirtimeResponse whereMessage($value)
 * @method static Builder|AirtimeResponse wherePhone($value)
 * @method static Builder|AirtimeResponse whereRequestId($value)
 * @method static Builder|AirtimeResponse whereStatus($value)
 * @method static Builder|AirtimeResponse whereUpdatedAt($value)
 * @mixin IdeHelperAirtimeResponse
 */
class AirtimeResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'message',
        'amount',
        'discount',
        'request_id'
    ];

    public function airtimeRequest(): BelongsTo
    {
        return $this->belongsTo(AirtimeRequest::class);
    }
}
