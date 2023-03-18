<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\TransactionType;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Models\KyandaRequest;

/**
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'product_id',
        'initiator',
        'type',
        'amount',
        'charge',
        'status',
        'destination',
        'description',
    ];

    protected $casts = [
        'type' => TransactionType::class,
    ];

    /**
     * Internal relations
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    /**
     * External service relations
     */
    public function atAirtimeRequest(): HasOne
    {
        return $this->hasOne(ATAirtimeRequest::class);
    }

    public function kyandaTransaction(): HasOne
    {
        return $this->hasOne(KyandaRequest::class, 'relation_id');
    }

    public function tandaRequests(): HasMany
    {
        return $this->hasMany(TandaRequest::class, 'relation_id');
    }

    public function savingsTransaction(): HasOne
    {
        return $this->hasOne(SavingsTransaction::class);
    }

    /**
     * Accessors & Mutators
     */
    protected function totalAmount(): Attribute
    {
        return Attribute::get(fn (mixed $value, array $attributes) => $attributes['amount'] + $attributes['charge']);
    }

    public function updateStatus(Status $status)
    {
        Log::info('...[MDL - TRANSACTION]: Update Status...', [
            'status' => $status->value,
        ]);

        $this->update(['status' => $status]);
    }
}
