<?php

namespace App\Models;

use Database\Factories\FloatAccountTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\FloatAccountTransaction
 *
 * @property int $id
 * @property string $type
 * @property string $amount
 * @property string $description
 * @property int $float_account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static FloatAccountTransactionFactory factory(...$parameters)
 * @method static Builder|FloatAccountTransaction newModelQuery()
 * @method static Builder|FloatAccountTransaction newQuery()
 * @method static Builder|FloatAccountTransaction query()
 * @method static Builder|FloatAccountTransaction whereAmount($value)
 * @method static Builder|FloatAccountTransaction whereCreatedAt($value)
 * @method static Builder|FloatAccountTransaction whereDescription($value)
 * @method static Builder|FloatAccountTransaction whereFloatAccountId($value)
 * @method static Builder|FloatAccountTransaction whereId($value)
 * @method static Builder|FloatAccountTransaction whereType($value)
 * @method static Builder|FloatAccountTransaction whereUpdatedAt($value)
 * @mixin IdeHelperFloatAccountTransaction
 */
class FloatAccountTransaction extends Model
{
    use HasFactory;
}
