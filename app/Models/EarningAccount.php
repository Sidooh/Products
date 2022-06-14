<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperEarningAccount
 */
class EarningAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'amount',
        'type',
    ];
}
