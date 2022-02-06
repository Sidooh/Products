<?php

namespace App\Models;

use Database\Factories\PaymentFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Payment
 *
 * @property int                             $id
 * @property string                          $payable_type
 * @property int                             $payable_id
 * @property string                          $amount
 * @property string                          $status
 * @property string                          $type
 * @property string                          $subtype
 * @property string                          $provider_type
 * @property int                             $provider_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent             $payable
 * @method static PaymentFactory factory(...$parameters)
 * @method static Builder|Payment newModelQuery()
 * @method static Builder|Payment newQuery()
 * @method static Builder|Payment query()
 * @method static Builder|Payment whereAmount($value)
 * @method static Builder|Payment whereCreatedAt($value)
 * @method static Builder|Payment whereId($value)
 * @method static Builder|Payment wherePayableId($value)
 * @method static Builder|Payment wherePayableType($value)
 * @method static Builder|Payment whereProviderId($value)
 * @method static Builder|Payment whereProviderType($value)
 * @method static Builder|Payment whereStatus($value)
 * @method static Builder|Payment whereSubtype($value)
 * @method static Builder|Payment whereType($value)
 * @method static Builder|Payment whereUpdatedAt($value)
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'amount',
        'status',
        'type',
        'subtype',
        'provider_type',
        'provider_id',
    ];

    /**
     * Get the parent payable model (transaction or ...).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
