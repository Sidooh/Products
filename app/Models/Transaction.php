<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\TransactionType;
use DrH\Tanda\Models\TandaRequest;
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
        'status',
        'destination',
        'description',
    ];

    protected $casts = [
        'type' => TransactionType::class,
    ];

    // Internal relations
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

//    TODO: Is it being used?
    public function cashbacks(): HasMany
    {
        return $this->hasMany(Cashback::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // External service relations
    public function atAirtimeRequest(): HasOne
    {
        return $this->hasOne(ATAirtimeRequest::class);
    }

    public function kyandaTransaction(): HasOne
    {
        return $this->hasOne(KyandaRequest::class, 'relation_id');
    }

    public function tandaRequest(): HasOne
    {
        return $this->hasOne(TandaRequest::class, 'relation_id');
    }

    public function savingsTransaction(): HasOne
    {
        return $this->hasOne(SavingsTransaction::class);
    }

    // Methods
    public static function updateStatus(self $transaction, Status $status = Status::PENDING)
    {
        Log::info('...[MDL - TRANSACTION]: Update Status...', [
            'status' => $status->value,
        ]);

        $transaction->status = $status;
        $transaction->save();
    }
}
