<?php

namespace App\Models;

use Database\Factories\VoucherTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\VoucherTransaction
 *
 * @property int                             $id
 * @property string                          $type
 * @property string                          $amount
 * @property string                          $description
 * @property int                             $voucher_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Voucher                    $voucher
 * @method static VoucherTransactionFactory factory(...$parameters)
 * @method static Builder|VoucherTransaction newModelQuery()
 * @method static Builder|VoucherTransaction newQuery()
 * @method static Builder|VoucherTransaction query()
 * @method static Builder|VoucherTransaction whereAmount($value)
 * @method static Builder|VoucherTransaction whereCreatedAt($value)
 * @method static Builder|VoucherTransaction whereDescription($value)
 * @method static Builder|VoucherTransaction whereId($value)
 * @method static Builder|VoucherTransaction whereType($value)
 * @method static Builder|VoucherTransaction whereUpdatedAt($value)
 * @method static Builder|VoucherTransaction whereVoucherId($value)
 * @mixin IdeHelperVoucherTransaction
 */
class VoucherTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'description',
    ];

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}
