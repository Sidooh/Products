<?php

namespace App\Models;

use Database\Factories\ProductAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProductAccount
 *
 * @property int $id
 * @property string $provider
 * @property string $account_number
 * @property int $account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static ProductAccountFactory factory(...$parameters)
 * @method static Builder|ProductAccount newModelQuery()
 * @method static Builder|ProductAccount newQuery()
 * @method static Builder|ProductAccount query()
 * @method static Builder|ProductAccount whereAccountId($value)
 * @method static Builder|ProductAccount whereAccountNumber($value)
 * @method static Builder|ProductAccount whereCreatedAt($value)
 * @method static Builder|ProductAccount whereId($value)
 * @method static Builder|ProductAccount whereProvider($value)
 * @method static Builder|ProductAccount whereUpdatedAt($value)
 * @mixin IdeHelperProductAccount
 */
class ProductAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'provider',
        'account_number'
    ];
}
