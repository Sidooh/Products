<?php

namespace App\Models;

use Database\Factories\FloatAccountFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\FloatAccount
 *
 * @property int                             $id
 * @property string                          $balance
 * @property string                          $accountable_type
 * @property int                             $accountable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent             $accountable
 * @method static FloatAccountFactory factory(...$parameters)
 * @method static Builder|FloatAccount newModelQuery()
 * @method static Builder|FloatAccount newQuery()
 * @method static Builder|FloatAccount query()
 * @method static Builder|FloatAccount whereAccountableId($value)
 * @method static Builder|FloatAccount whereAccountableType($value)
 * @method static Builder|FloatAccount whereBalance($value)
 * @method static Builder|FloatAccount whereCreatedAt($value)
 * @method static Builder|FloatAccount whereId($value)
 * @method static Builder|FloatAccount whereUpdatedAt($value)
 * @mixin IdeHelperFloatAccount
 */
class FloatAccount extends Model
{
    use HasFactory;

    /**
     * Get the parent accountable model (agent or enterprise).
     */
    public function accountable(): MorphTo
    {
        return $this->morphTo();
    }
}
