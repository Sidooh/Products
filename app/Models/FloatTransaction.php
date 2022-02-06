<?php

namespace App\Models;

use Database\Factories\FloatTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FloatTransaction
 *
 * @method static FloatTransactionFactory factory(...$parameters)
 * @method static Builder|FloatTransaction newModelQuery()
 * @method static Builder|FloatTransaction newQuery()
 * @method static Builder|FloatTransaction query()
 * @mixin IdeHelperFloatTransaction
 */
class FloatTransaction extends Model
{
    use HasFactory;
}
