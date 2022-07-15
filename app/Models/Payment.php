<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        "status",
        'transaction_id',
        'payment_id'
    ];

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
