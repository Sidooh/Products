<?php

namespace App\Models;

use Database\Factories\EnterpriseAccountFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\EnterpriseAccount
 *
 * @property int                             $id
 * @property string                          $type
 * @property int                             $active
 * @property int                             $account_id
 * @property int                             $enterprise_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Enterprise                 $enterprise
 *
 * @method static EnterpriseAccountFactory factory(...$parameters)
 * @method static Builder|EnterpriseAccount newModelQuery()
 * @method static Builder|EnterpriseAccount newQuery()
 * @method static Builder|EnterpriseAccount query()
 * @method static Builder|EnterpriseAccount whereAccountId($value)
 * @method static Builder|EnterpriseAccount whereActive($value)
 * @method static Builder|EnterpriseAccount whereCreatedAt($value)
 * @method static Builder|EnterpriseAccount whereEnterpriseId($value)
 * @method static Builder|EnterpriseAccount whereId($value)
 * @method static Builder|EnterpriseAccount whereType($value)
 * @method static Builder|EnterpriseAccount whereUpdatedAt($value)
 * @mixin IdeHelperEnterpriseAccount
 */
class EnterpriseAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'type',
    ];

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }
}
