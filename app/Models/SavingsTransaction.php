<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSavingsTransaction
 */
class SavingsTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'savings_id',
        'reference',
        'type',
        'amount',
        'charge',
        'status',
        'description',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
