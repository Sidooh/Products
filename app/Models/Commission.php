<?php

namespace App\Models;

use Database\Factories\CommissionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\Commission
 *
 * @property int $id
 * @property string $amount
 * @property string $type
 * @property int|null $account_id
 * @property int $transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static CommissionFactory factory(...$parameters)
 * @method static Builder|Commission newModelQuery()
 * @method static Builder|Commission newQuery()
 * @method static Builder|Commission query()
 * @method static Builder|Commission whereAccountId($value)
 * @method static Builder|Commission whereAmount($value)
 * @method static Builder|Commission whereCreatedAt($value)
 * @method static Builder|Commission whereId($value)
 * @method static Builder|Commission whereTransactionId($value)
 * @method static Builder|Commission whereType($value)
 * @method static Builder|Commission whereUpdatedAt($value)
 *
 * @mixin IdeHelperCommission
 */
class Commission extends Model
{
    use HasFactory;
}
