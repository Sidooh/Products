<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */

namespace App\Models{
    /**
     * App\Models\ATAirtimeRequest
     *
     * @property int $id
     * @property string $message
     * @property int $num_sent
     * @property string $amount
     * @property string $discount
     * @property string $description
     * @property int|null $transaction_id
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     *
     * @method static AirtimeRequestFactory factory(...$parameters)
     * @method static Builder|ATAirtimeRequest newModelQuery()
     * @method static Builder|ATAirtimeRequest newQuery()
     * @method static Builder|ATAirtimeRequest query()
     * @method static Builder|ATAirtimeRequest whereAmount($value)
     * @method static Builder|ATAirtimeRequest whereCreatedAt($value)
     * @method static Builder|ATAirtimeRequest whereDescription($value)
     * @method static Builder|ATAirtimeRequest whereDiscount($value)
     * @method static Builder|ATAirtimeRequest whereId($value)
     * @method static Builder|ATAirtimeRequest whereMessage($value)
     * @method static Builder|ATAirtimeRequest whereNumSent($value)
     * @method static Builder|ATAirtimeRequest whereTransactionId($value)
     * @method static Builder|ATAirtimeRequest whereUpdatedAt($value)
     *
     * @property-read Collection|ATAirtimeResponse[] $airtimeResponses
     * @property-read int|null $airtime_responses_count
     * @property-read Transaction|null $transaction
     * @property-read \App\Models\ATAirtimeResponse|null $response
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ATAirtimeResponse> $responses
     * @property-read int|null $responses_count
     *
     * @mixin \Eloquent
     */
    class IdeHelperATAirtimeRequest
    {
    }
}

namespace App\Models{
    /**
     * App\Models\ATAirtimeResponse
     *
     * @property int $id
     * @property string $phone
     * @property string $message
     * @property string $amount
     * @property string $status
     * @property string $request_id
     * @property string $discount
     * @property int $airtime_request_id
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     *
     * @method static AirtimeResponseFactory factory(...$parameters)
     * @method static Builder|ATAirtimeResponse newModelQuery()
     * @method static Builder|ATAirtimeResponse newQuery()
     * @method static Builder|ATAirtimeResponse query()
     * @method static Builder|ATAirtimeResponse whereAirtimeRequestId($value)
     * @method static Builder|ATAirtimeResponse whereAmount($value)
     * @method static Builder|ATAirtimeResponse whereCreatedAt($value)
     * @method static Builder|ATAirtimeResponse whereDiscount($value)
     * @method static Builder|ATAirtimeResponse whereId($value)
     * @method static Builder|ATAirtimeResponse whereMessage($value)
     * @method static Builder|ATAirtimeResponse wherePhone($value)
     * @method static Builder|ATAirtimeResponse whereRequestId($value)
     * @method static Builder|ATAirtimeResponse whereStatus($value)
     * @method static Builder|ATAirtimeResponse whereUpdatedAt($value)
     *
     * @property string|null $description
     * @property-read \App\Models\ATAirtimeRequest $airtimeRequest
     *
     * @method static Builder|ATAirtimeResponse whereDescription($value)
     *
     * @property int $at_airtime_request_id
     * @property-read \App\Models\ATAirtimeRequest|null $request
     *
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ATAirtimeResponse whereAtAirtimeRequestId($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperATAirtimeResponse
    {
    }
}

namespace App\Models{
    /**
     * App\Models\AirtimeAccount
     *
     * @property int $id
     * @property string $provider
     * @property string $account_number
     * @property int $priority
     * @property int $account_id
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     *
     * @method static \Database\Factories\AirtimeAccountFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount whereAccountId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount whereAccountNumber($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount wherePriority($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount whereProvider($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\AirtimeAccount whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperAirtimeAccount
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Cashback
     *
     * @property int         $id
     * @property string      $amount
     * @property string      $type
     * @property int|null    $account_id
     * @property int         $transaction_id
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     *
     * @method static CashbackFactory factory(...$parameters)
     * @method static Builder|Cashback newModelQuery()
     * @method static Builder|Cashback newQuery()
     * @method static Builder|Cashback query()
     * @method static Builder|Cashback whereAccountId($value)
     * @method static Builder|Cashback whereAmount($value)
     * @method static Builder|Cashback whereCreatedAt($value)
     * @method static Builder|Cashback whereId($value)
     * @method static Builder|Cashback whereTransactionId($value)
     * @method static Builder|Cashback whereType($value)
     * @method static Builder|Cashback whereUpdatedAt($value)
     *
     * @property \App\Enums\Status $status
     * @property-read \App\Models\Transaction $transaction
     *
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Cashback whereStatus($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperCashback
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Commission
     *
     * @property int $id
     * @property string $amount
     * @property string $type
     * @property int|null $account_id
     * @property int $transaction_id
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     *
     * @method static CommissionFactory factory(...$parameters)
     * @method static Builder|Commission newModelQuery()
     * @method static Builder|Commission newQuery()
     * @method static Builder|Commission query()
     * @method static Builder|Commission whereAccountId($value)
     * @method static Builder|Commission whereAmount($value)
     * @method static Builder|Commission whereCreatedAt($value)
     * @method static Builder|Commission whereId($value)
     * @method static Builder|Commission whereTransactionId($value)
     * @method static Builder|Commission whereType($value)
     * @method static Builder|Commission whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperCommission
    {
    }
}

namespace App\Models{
    /**
     * App\Models\EarningAccount
     *
     * @property int $id
     * @property \App\Enums\EarningAccountType $type
     * @property string $self_amount
     * @property string $invite_amount
     * @property int $account_id
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     *
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount accountId(int $accountId)
     * @method static \Database\Factories\EarningAccountFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereAccountId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereInviteAmount($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereSelfAmount($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereType($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\EarningAccount withdrawal()
     *
     * @mixin \Eloquent
     */
    class IdeHelperEarningAccount
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Merchant
     *
     * @property int $id
     * @property string $name
     * @property string $code
     * @property string $contact_name
     * @property string $contact_phone
     * @property string $balance
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     *
     * @method static \Database\Factories\MerchantFactory factory(...$parameters)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant query()
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereBalance($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereCode($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereContactName($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereContactPhone($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder|Merchant whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperMerchant
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Notification
     *
     * @property int $id
     * @property array $to
     * @property string $message
     * @property string $event
     * @property array $response
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     *
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereEvent($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereMessage($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereResponse($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereTo($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Notification whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperNotification
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Payment
     *
     * @property int $id
     * @property int $payment_id
     * @property string $amount
     * @property int $charge
     * @property string $type
     * @property string $subtype
     * @property string $status
     * @property array|null $extra
     * @property int $transaction_id
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \App\Models\Transaction $transaction
     *
     * @method static \Database\Factories\PaymentFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereAmount($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereCharge($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereExtra($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment wherePaymentId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereStatus($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereSubtype($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereTransactionId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereType($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Payment whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperPayment
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Product
     *
     * @property int $id
     * @property string $name
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Transaction> $transaction
     * @property-read int|null $transaction_count
     *
     * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereName($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Product whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperProduct
    {
    }
}

namespace App\Models{
    /**
     * App\Models\SavingsTransaction
     *
     * @property int $id
     * @property int $savings_id
     * @property string $amount
     * @property int $charge
     * @property string $description
     * @property string $type
     * @property string $status
     * @property array|null $extra
     * @property int $transaction_id
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \App\Models\Transaction $transaction
     *
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereAmount($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereCharge($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereDescription($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereExtra($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereSavingsId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereStatus($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereTransactionId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereType($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SavingsTransaction whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperSavingsTransaction
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Subscription
     *
     * @property int                             $id
     * @property string                          $amount
     * @property string                          $start_date
     * @property string                          $end_date
     * @property string                          $status
     * @property int                             $account_id
     * @property int                             $subscription_type_id
     * @property Carbon|null $created_at
     * @property Carbon|null $updated_at
     * @property-read SubscriptionType           $subscriptionType
     *
     * @method static SubscriptionFactory factory(...$parameters)
     * @method static Builder|Subscription newModelQuery()
     * @method static Builder|Subscription newQuery()
     * @method static Builder|Subscription query()
     * @method static Builder|Subscription whereAccountId($value)
     * @method static Builder|Subscription whereAmount($value)
     * @method static Builder|Subscription whereCreatedAt($value)
     * @method static Builder|Subscription whereEndDate($value)
     * @method static Builder|Subscription whereId($value)
     * @method static Builder|Subscription whereStartDate($value)
     * @method static Builder|Subscription whereStatus($value)
     * @method static Builder|Subscription whereSubscriptionTypeId($value)
     * @method static Builder|Subscription whereUpdatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription includePostExpiry()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Subscription includePreExpiry()
     *
     * @mixin \Eloquent
     */
    class IdeHelperSubscription
    {
    }
}

namespace App\Models{
    /**
     * App\Models\SubscriptionType
     *
     * @property int                                                          $id
     * @property string                                                       $title
     * @property string                                                       $price
     * @property int                                                          $level_limit
     * @property int                                                          $duration
     * @property int                                                          $active
     * @property Carbon|null                              $created_at
     * @property Carbon|null                              $updated_at
     * @property-read Collection|Subscription[] $subscription
     * @property-read int|null                                                $subscription_count
     *
     * @method static SubscriptionTypeFactory factory(...$parameters)
     * @method static Builder|SubscriptionType newModelQuery()
     * @method static Builder|SubscriptionType newQuery()
     * @method static Builder|SubscriptionType query()
     * @method static Builder|SubscriptionType whereActive($value)
     * @method static Builder|SubscriptionType whereCreatedAt($value)
     * @method static Builder|SubscriptionType whereDuration($value)
     * @method static Builder|SubscriptionType whereId($value)
     * @method static Builder|SubscriptionType whereLevelLimit($value)
     * @method static Builder|SubscriptionType wherePrice($value)
     * @method static Builder|SubscriptionType whereTitle($value)
     * @method static Builder|SubscriptionType whereUpdatedAt($value)
     *
     * @property string $period
     *
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\SubscriptionType wherePeriod($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperSubscriptionType
    {
    }
}

namespace App\Models{
    /**
     * App\Models\Transaction
     *
     * @property int $id
     * @property string $initiator
     * @property \App\Enums\TransactionType $type
     * @property string $amount
     * @property int $charge
     * @property string $status
     * @property string $description
     * @property string|null $destination
     * @property int $account_id
     * @property int $product_id
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     * @property-read \App\Models\ATAirtimeRequest|null $atAirtimeRequest
     * @property-read \Nabcellent\Kyanda\Models\KyandaRequest|null $kyandaTransaction
     * @property-read \App\Models\Payment|null $payment
     * @property-read \App\Models\Product $product
     * @property-read \App\Models\SavingsTransaction|null $savingsTransaction
     * @property-read \DrH\Tanda\Models\TandaRequest|null $tandaRequest
     * @property-read \Illuminate\Database\Eloquent\Collection<int, \DrH\Tanda\Models\TandaRequest> $tandaRequests
     * @property-read int|null $tanda_requests_count
     *
     * @method static \Database\Factories\TransactionFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereAccountId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereAmount($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereCharge($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereDescription($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereDestination($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereInitiator($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereProductId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereStatus($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereType($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Transaction whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperTransaction
    {
    }
}

namespace App\Models{
    /**
     * App\Models\UtilityAccount
     *
     * @property int $id
     * @property string $provider
     * @property string $account_number
     * @property int $priority
     * @property int $account_id
     * @property \Illuminate\Support\Carbon|null $created_at
     * @property \Illuminate\Support\Carbon|null $updated_at
     *
     * @method static \Database\Factories\UtilityAccountFactory factory($count = null, $state = [])
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount newModelQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount newQuery()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount query()
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount whereAccountId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount whereAccountNumber($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount whereCreatedAt($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount whereId($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount wherePriority($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount whereProvider($value)
     * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\UtilityAccount whereUpdatedAt($value)
     *
     * @mixin \Eloquent
     */
    class IdeHelperUtilityAccount
    {
    }
}
