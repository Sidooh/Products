<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
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
