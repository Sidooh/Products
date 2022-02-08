<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Nabcellent\Kyanda\Models\KyandaRequest;

/**
 * App\Models\Transaction
 *
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'initiator',
        'type',
        'amount',
        'destination',
        'description',
    ];

    /**
     * Get the transaction's payment.
     */
    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function airtime(): HasOne
    {
        return $this->hasOne(AirtimeRequest::class);
    }

    public function kyandaTransaction(): HasOne
    {
        return $this->hasOne(KyandaRequest::class, 'relation_id');
    }

    public function airtimeRequest(): HasOne
    {
        return $this->hasOne(AirtimeRequest::class);
    }


    public static function updateStatus(self $transaction, Status $status = Status::PENDING)
    {
        $transaction->status = $status;
        $transaction->save();
    }
}
