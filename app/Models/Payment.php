<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'transaction_id',
        'payment_id',
        'type',
        'amount',
        'charge',
        'subtype',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereStatus(Status::PENDING->name);
    }
}
