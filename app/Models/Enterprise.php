<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperEnterprise
 */
class Enterprise extends Model
{
    use HasFactory;

    protected $fillable= [
        "name",
        "settings"
    ];

    protected $casts = [
        'settings' => 'array'
    ];

    /**
     * ---------------------------------------- Relationships ----------------------------------------
     */
    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }
}
