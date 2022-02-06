<?php

namespace App\Models;

use App\Enums\VoucherType;
use Database\Factories\VoucherFactory;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Voucher
 *
 * @property int                                  $id
 * @property string                               $type
 * @property string                               $balance
 * @property int                                  $account_id
 * @property int|null                             $enterprise_id
 * @property Carbon|null      $created_at
 * @property Carbon|null      $updated_at
 * @property-read Enterprise|null                 $enterprise
 * @property-read Collection|VoucherTransaction[] $voucherTransaction
 * @property-read int|null                        $voucher_transaction_count
 * @method static VoucherFactory factory(...$parameters)
 * @method static Builder|Voucher newModelQuery()
 * @method static Builder|Voucher newQuery()
 * @method static Builder|Voucher query()
 * @method static Builder|Voucher whereAccountId($value)
 * @method static Builder|Voucher whereBalance($value)
 * @method static Builder|Voucher whereCreatedAt($value)
 * @method static Builder|Voucher whereEnterpriseId($value)
 * @method static Builder|Voucher whereId($value)
 * @method static Builder|Voucher whereType($value)
 * @method static Builder|Voucher whereUpdatedAt($value)
 * @mixin IdeHelperVoucher
 */
class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'type'
    ];

    public function voucherTopUpAmount(): Attribute
    {
        return new Attribute(get: function($value, $attributes) {

            $disburseType = match (VoucherType::from($this->type)) {
                VoucherType::ENTERPRISE_LUNCH => 'lunch',
                VoucherType::ENTERPRISE_GENERAL => 'general',
                default => throw new Exception('Unexpected match value'),
            };

            ['max' => $max] = collect($this->enterprise->settings)->firstWhere('type', $disburseType);

            return $max - $attributes['balance'];
        });
    }

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function voucherTransaction(): HasMany
    {
        return $this->hasMany(VoucherTransaction::class);
    }

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }
}
