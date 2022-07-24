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
 * App\Models\AirtimeAccount
 *
 * @property int $id
 * @property string $provider
 * @property string $account_number
 * @property int $priority
 * @property int $account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\AirtimeAccountFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AirtimeAccount whereUpdatedAt($value)
 */
	class IdeHelperAirtimeAccount {}
}

namespace App\Models{
/**
 * App\Models\AirtimeRequest
 *
 * @property int                             $id
 * @property string                          $message
 * @property int                             $num_sent
 * @property string                          $amount
 * @property string                          $discount
 * @property string                          $description
 * @property int|null                        $transaction_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static AirtimeRequestFactory factory(...$parameters)
 * @method static Builder|AirtimeRequest newModelQuery()
 * @method static Builder|AirtimeRequest newQuery()
 * @method static Builder|AirtimeRequest query()
 * @method static Builder|AirtimeRequest whereAmount($value)
 * @method static Builder|AirtimeRequest whereCreatedAt($value)
 * @method static Builder|AirtimeRequest whereDescription($value)
 * @method static Builder|AirtimeRequest whereDiscount($value)
 * @method static Builder|AirtimeRequest whereId($value)
 * @method static Builder|AirtimeRequest whereMessage($value)
 * @method static Builder|AirtimeRequest whereNumSent($value)
 * @method static Builder|AirtimeRequest whereTransactionId($value)
 * @method static Builder|AirtimeRequest whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ATAirtimeResponse[] $airtimeResponses
 * @property-read int|null $airtime_responses_count
 * @property-read \App\Models\Transaction|null $transaction
 */
	class IdeHelperAirtimeRequest {}
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
     * @property string|null $description
     * @property-read \App\Models\AirtimeRequest $airtimeRequest
     * @method static Builder|ATAirtimeResponse whereDescription($value)
     */
	class IdeHelperAirtimeResponse {}
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
 * @property-read \App\Models\Transaction $transaction
 */
	class IdeHelperCashback {}
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
 */
	class IdeHelperCommission {}
}

namespace App\Models{
/**
 * App\Models\EarningAccount
 *
 * @property int $id
 * @property string $type
 * @property string $self_amount
 * @property string $invite_amount
 * @property int $account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount accountId(int $accountId)
 * @method static \Database\Factories\EarningAccountFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereInviteAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereSelfAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EarningAccount withdrawal()
 */
	class IdeHelperEarningAccount {}
}

namespace App\Models{
/**
 * App\Models\Enterprise
 *
 * @property int                                 $id
 * @property string                              $name
 * @property array                               $settings
 * @property Carbon|null     $created_at
 * @property Carbon|null     $updated_at
 * @property-read Collection|EnterpriseAccount[] $enterpriseAccounts
 * @property-read int|null                       $enterprise_accounts_count
 * @property-read FloatAccount|null              $floatAccount
 * @property-read Collection|Voucher[]           $vouchers
 * @property-read int|null                       $vouchers_count
 * @method static EnterpriseFactory factory(...$parameters)
 * @method static Builder|Enterprise newModelQuery()
 * @method static Builder|Enterprise newQuery()
 * @method static Builder|Enterprise query()
 * @method static Builder|Enterprise whereCreatedAt($value)
 * @method static Builder|Enterprise whereId($value)
 * @method static Builder|Enterprise whereName($value)
 * @method static Builder|Enterprise whereSettings($value)
 * @method static Builder|Enterprise whereUpdatedAt($value)
 */
	class IdeHelperEnterprise {}
}

namespace App\Models{
/**
 * App\Models\EnterpriseAccount
 *
 * @property int                             $id
 * @property string                          $type
 * @property int                             $active
 * @property int                             $account_id
 * @property int                             $enterprise_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Enterprise                 $enterprise
 * @method static EnterpriseAccountFactory factory(...$parameters)
 * @method static Builder|EnterpriseAccount newModelQuery()
 * @method static Builder|EnterpriseAccount newQuery()
 * @method static Builder|EnterpriseAccount query()
 * @method static Builder|EnterpriseAccount whereAccountId($value)
 * @method static Builder|EnterpriseAccount whereActive($value)
 * @method static Builder|EnterpriseAccount whereCreatedAt($value)
 * @method static Builder|EnterpriseAccount whereEnterpriseId($value)
 * @method static Builder|EnterpriseAccount whereId($value)
 * @method static Builder|EnterpriseAccount whereType($value)
 * @method static Builder|EnterpriseAccount whereUpdatedAt($value)
 */
	class IdeHelperEnterpriseAccount {}
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
 */
	class IdeHelperMerchant {}
}

namespace App\Models{
/**
 * App\Models\Payment
 *
 * @property int $id
 * @property int $transaction_id
 * @property int $payment_id
 * @property string $amount
 * @property string $type
 * @property string $subtype
 * @property string $status
 * @property array $extra
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Transaction $transaction
 * @method static \Database\Factories\PaymentFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment wherePaymentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Payment whereUpdatedAt($value)
 */
	class IdeHelperPayment {}
}

namespace App\Models{
/**
 * App\Models\Product
 *
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Transaction[] $transaction
 * @property-read int|null $transaction_count
 * @method static \Database\Factories\ProductFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Product whereUpdatedAt($value)
 */
	class IdeHelperProduct {}
}

namespace App\Models{
/**
 * App\Models\SavingsTransaction
 *
 * @property int $id
 * @property string|null $reference
 * @property string $type
 * @property string $amount
 * @property string $status
 * @property string $description
 * @property int $transaction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Transaction $transaction
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SavingsTransaction whereUpdatedAt($value)
 */
	class IdeHelperSavingsTransaction {}
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
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription includePostExpiry()
 * @method static \Illuminate\Database\Eloquent\Builder|Subscription includePreExpiry()
 */
	class IdeHelperSubscription {}
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
 * @property string $period
 * @method static \Illuminate\Database\Eloquent\Builder|SubscriptionType wherePeriod($value)
 */
	class IdeHelperSubscriptionType {}
}

namespace App\Models{
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
 * @property int|null $product_id
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Cashback[] $cashbacks
 * @property-read int|null $cashbacks_count
 * @property-read \App\Models\Payment|null $payment
 * @property-read \App\Models\Product|null $product
 * @property-read \DrH\Tanda\Models\TandaRequest|null $request
 * @property-read \App\Models\SavingsTransaction|null $savingsTransaction
 * @method static \Illuminate\Database\Eloquent\Builder|Transaction whereProductId($value)
 */
	class IdeHelperTransaction {}
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
 * @method static \Database\Factories\UtilityAccountFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount query()
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UtilityAccount whereUpdatedAt($value)
 */
	class IdeHelperUtilityAccount {}
}

