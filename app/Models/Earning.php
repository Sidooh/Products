<?php

namespace App\Models;

use Database\Factories\EarningFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Earning
 *
 * @method static EarningFactory factory(...$parameters)
 * @method static Builder|Earning newModelQuery()
 * @method static Builder|Earning newQuery()
 * @method static Builder|Earning query()
 * @mixin IdeHelperEarning
 */
class Earning extends Model
{
    use HasFactory;
}
