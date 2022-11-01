<?php

namespace App\Models;

use App\Enums\Status;
use Database\Factories\TransactionFactory;
use DrH\Tanda\Models\TandaRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Nabcellent\Kyanda\Models\KyandaRequest;

/**
 * App\Models\Transaction
 *
 * @property int $id
 * @property string $initiator
 * @property string $type
 * @property string $amount
 * @property string $status
 * @property string|null $destination
 * @property string $description
 * @property int $account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ATAirtimeRequest|null $atAirtimeRequest
 * @property-read KyandaRequest|null $kyandaTransaction
 * @property-read TandaRequest|null $tandaRequest
 *
 * @method static TransactionFactory factory(...$parameters)
 * @method static Builder|Transaction newModelQuery()
 * @method static Builder|Transaction newQuery()
 * @method static Builder|Transaction query()
 * @method static Builder|Transaction whereAccountId($value)
 * @method static Builder|Transaction whereAmount($value)
 * @method static Builder|Transaction whereCreatedAt($value)
 * @method static Builder|Transaction whereDescription($value)
 * @method static Builder|Transaction whereDestination($value)
 * @method static Builder|Transaction whereId($value)
 * @method static Builder|Transaction whereInitiator($value)
 * @method static Builder|Transaction whereStatus($value)
 * @method static Builder|Transaction whereType($value)
 * @method static Builder|Transaction whereUpdatedAt($value)
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
        'destination',
        'description',
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
