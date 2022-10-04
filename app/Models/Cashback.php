<?php

namespace App\Models;

use Database\Factories\CashbackFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Cashback
 *
 * @property int         $id
 * @property string      $amount
 * @property string      $type
 * @property int|null    $account_id
 * @property int         $transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static CashbackFactory factory(...$parameters)
 * @method static Builder|Cashback newModelQuery()
 * @method static Builder|Cashback newQuery()
 * @method static Builder|Cashback query()
 * @method static Builder|Cashback whereAccountId($value)
 * @method static Builder|Cashback whereAmount($value)
 * @method static Builder|Cashback whereCreatedAt($value)
 * @method static Builder|Cashback whereId($value)
 * @method static Builder|Cashback whereTransactionId($value)
 * @method static Builder|Cashback whereType($value)
 * @method static Builder|Cashback whereUpdatedAt($value)
 * @mixin IdeHelperCashback
 */
class Cashback extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'amount',
        'type',
        'transaction_id',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
