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
 * @property int                      $id
 * @property string                   $initiator
 * @property string                   $type
 * @property string                   $amount
 * @property string                   $status
 * @property string|null              $destination
 * @property string                   $description
 * @property int                      $account_id
 * @property Carbon|null              $created_at
 * @property Carbon|null              $updated_at
 * @property-read AirtimeRequest|null $airtime
 * @property-read AirtimeRequest|null $airtimeRequest
 * @property-read KyandaRequest|null  $kyandaTransaction
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

    public function airtime(): HasOne
    {
        return $this->hasOne(AirtimeRequest::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function kyandaTransaction(): HasOne
    {
        return $this->hasOne(KyandaRequest::class, 'relation_id');
    }

    public function airtimeRequest(): HasOne
    {
        return $this->hasOne(AirtimeRequest::class);
    }

    public function cashbacks(): HasMany
    {
        return $this->hasMany(Cashback::class);
    }

    public function request(): HasOne
    {
        return $this->hasOne(TandaRequest::class, 'relation_id');
    }

    public function savingsTransaction(): HasOne
    {
        return $this->hasOne(SavingsTransaction::class);
    }

    public static function updateStatus(self $transaction, Status $status = Status::PENDING)
    {
        Log::info('...[TRANSACTION MODEL]: Update Status...', [
            "status" => $status->value
        ]);

        $transaction->status = $status;
        $transaction->save();
    }
}
