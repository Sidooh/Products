<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Nabcellent\Kyanda\Models\KyandaRequest;

/**
 * @mixin IdeHelperTransaction
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'initiator',
        'type',
        'amount',
        'description'
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
}
