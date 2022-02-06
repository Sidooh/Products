<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{use Database\Factories\AirtimeRequestFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;
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
 * @property-read Collection|AirtimeResponse[] $airtimeResponses
 * @property-read int|null $airtime_responses_count
 * @property-read Transaction|null $transaction
 */
	class IdeHelperAirtimeRequest {}
}

namespace App\Models{use Database\Factories\AirtimeResponseFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\AirtimeResponse
 *
 * @property int                             $id
 * @property string                          $phone
 * @property string                          $message
 * @property string                          $amount
 * @property string                          $status
 * @property string                          $request_id
 * @property string                          $discount
 * @property int                             $airtime_request_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static AirtimeResponseFactory factory(...$parameters)
 * @method static Builder|AirtimeResponse newModelQuery()
 * @method static Builder|AirtimeResponse newQuery()
 * @method static Builder|AirtimeResponse query()
 * @method static Builder|AirtimeResponse whereAirtimeRequestId($value)
 * @method static Builder|AirtimeResponse whereAmount($value)
 * @method static Builder|AirtimeResponse whereCreatedAt($value)
 * @method static Builder|AirtimeResponse whereDiscount($value)
 * @method static Builder|AirtimeResponse whereId($value)
 * @method static Builder|AirtimeResponse whereMessage($value)
 * @method static Builder|AirtimeResponse wherePhone($value)
 * @method static Builder|AirtimeResponse whereRequestId($value)
 * @method static Builder|AirtimeResponse whereStatus($value)
 * @method static Builder|AirtimeResponse whereUpdatedAt($value)
 * @property string $description
 * @property-read AirtimeRequest|null $airtimeResponses
 * @method static Builder|AirtimeResponse whereDescription($value)
 */
	class IdeHelperAirtimeResponse {}
}

namespace App\Models{use Database\Factories\CashbackFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\Cashback
 *
 * @property int $id
 * @property string $amount
 * @property string $type
 * @property int|null $account_id
 * @property int $transaction_id
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
 */
	class IdeHelperCashback {}
}

namespace App\Models{use Database\Factories\CommissionFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
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

namespace App\Models{use Database\Factories\EarningFactory;use Illuminate\Database\Eloquent\Builder;
/**
 * App\Models\Earning
 *
 * @method static EarningFactory factory(...$parameters)
 * @method static Builder|Earning newModelQuery()
 * @method static Builder|Earning newQuery()
 * @method static Builder|Earning query()
 */
	class IdeHelperEarning {}
}

namespace App\Models{use Database\Factories\EnterpriseFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;
/**
 * App\Models\Enterprise
 *
 * @property int $id
 * @property string $name
 * @property array $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|EnterpriseAccount[] $enterpriseAccounts
 * @property-read int|null $enterprise_accounts_count
 * @property-read FloatAccount|null $floatAccount
 * @property-read Collection|Voucher[] $vouchers
 * @property-read int|null $vouchers_count
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

namespace App\Models{use Database\Factories\EnterpriseAccountFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\EnterpriseAccount
 *
 * @property int $id
 * @property string $type
 * @property int $active
 * @property int $account_id
 * @property int $enterprise_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Enterprise $enterprise
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

namespace App\Models{use Database\Factories\FloatAccountFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\FloatAccount
 *
 * @property int $id
 * @property string $balance
 * @property string $accountable_type
 * @property int $accountable_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent $accountable
 * @method static FloatAccountFactory factory(...$parameters)
 * @method static Builder|FloatAccount newModelQuery()
 * @method static Builder|FloatAccount newQuery()
 * @method static Builder|FloatAccount query()
 * @method static Builder|FloatAccount whereAccountableId($value)
 * @method static Builder|FloatAccount whereAccountableType($value)
 * @method static Builder|FloatAccount whereBalance($value)
 * @method static Builder|FloatAccount whereCreatedAt($value)
 * @method static Builder|FloatAccount whereId($value)
 * @method static Builder|FloatAccount whereUpdatedAt($value)
 */
	class IdeHelperFloatAccount {}
}

namespace App\Models{use Database\Factories\FloatAccountTransactionFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\FloatAccountTransaction
 *
 * @property int $id
 * @property string $type
 * @property string $amount
 * @property string $description
 * @property int $float_account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static FloatAccountTransactionFactory factory(...$parameters)
 * @method static Builder|FloatAccountTransaction newModelQuery()
 * @method static Builder|FloatAccountTransaction newQuery()
 * @method static Builder|FloatAccountTransaction query()
 * @method static Builder|FloatAccountTransaction whereAmount($value)
 * @method static Builder|FloatAccountTransaction whereCreatedAt($value)
 * @method static Builder|FloatAccountTransaction whereDescription($value)
 * @method static Builder|FloatAccountTransaction whereFloatAccountId($value)
 * @method static Builder|FloatAccountTransaction whereId($value)
 * @method static Builder|FloatAccountTransaction whereType($value)
 * @method static Builder|FloatAccountTransaction whereUpdatedAt($value)
 */
	class IdeHelperFloatAccountTransaction {}
}

namespace App\Models{use Database\Factories\FloatTransactionFactory;use Illuminate\Database\Eloquent\Builder;
/**
 * App\Models\FloatTransaction
 *
 * @method static FloatTransactionFactory factory(...$parameters)
 * @method static Builder|FloatTransaction newModelQuery()
 * @method static Builder|FloatTransaction newQuery()
 * @method static Builder|FloatTransaction query()
 */
	class IdeHelperFloatTransaction {}
}

namespace App\Models{use Database\Factories\PaymentFactory;use Eloquent;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\Payment
 *
 * @property int $id
 * @property string $payable_type
 * @property int $payable_id
 * @property string $amount
 * @property string $status
 * @property string $type
 * @property string $subtype
 * @property string $provider_type
 * @property int $provider_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|Eloquent $payable
 * @method static PaymentFactory factory(...$parameters)
 * @method static Builder|Payment newModelQuery()
 * @method static Builder|Payment newQuery()
 * @method static Builder|Payment query()
 * @method static Builder|Payment whereAmount($value)
 * @method static Builder|Payment whereCreatedAt($value)
 * @method static Builder|Payment whereId($value)
 * @method static Builder|Payment wherePayableId($value)
 * @method static Builder|Payment wherePayableType($value)
 * @method static Builder|Payment whereProviderId($value)
 * @method static Builder|Payment whereProviderType($value)
 * @method static Builder|Payment whereStatus($value)
 * @method static Builder|Payment whereSubtype($value)
 * @method static Builder|Payment whereType($value)
 * @method static Builder|Payment whereUpdatedAt($value)
 */
	class IdeHelperPayment {}
}

namespace App\Models{use Database\Factories\ProductAccountFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\ProductAccount
 *
 * @property int $id
 * @property string $provider
 * @property string $account_number
 * @property int $account_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static ProductAccountFactory factory(...$parameters)
 * @method static Builder|ProductAccount newModelQuery()
 * @method static Builder|ProductAccount newQuery()
 * @method static Builder|ProductAccount query()
 * @method static Builder|ProductAccount whereAccountId($value)
 * @method static Builder|ProductAccount whereAccountNumber($value)
 * @method static Builder|ProductAccount whereCreatedAt($value)
 * @method static Builder|ProductAccount whereId($value)
 * @method static Builder|ProductAccount whereProvider($value)
 * @method static Builder|ProductAccount whereUpdatedAt($value)
 */
	class IdeHelperProductAccount {}
}

namespace App\Models{use Database\Factories\SubscriptionFactory;use Illuminate\Support\Carbon;
/**
 * App\Models\Subscription
 *
 * @property int $id
 * @property string $amount
 * @property string $start_date
 * @property string $end_date
 * @property string $status
 * @property int $account_id
 * @property int $subscription_type_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubscriptionType $subscriptionType
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
 */
	class IdeHelperSubscription {}
}

namespace App\Models{use Database\Factories\SubscriptionTypeFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;
/**
 * App\Models\SubscriptionType
 *
 * @property int $id
 * @property string $title
 * @property string $price
 * @property int $level_limit
 * @property int $duration
 * @property int $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection|Subscription[] $subscription
 * @property-read int|null $subscription_count
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
 */
	class IdeHelperSubscriptionType {}
}

namespace App\Models{use Database\Factories\TransactionFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
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
 * @property-read AirtimeRequest|null $airtime
 * @property-read KyandaRequest|null $kyandaTransaction
 * @property-read Payment|null $payment
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
 * @property-read AirtimeRequest|null $airtimeRequest
 */
	class IdeHelperTransaction {}
}

namespace App\Models{use Database\Factories\VoucherFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Database\Eloquent\Collection;use Illuminate\Support\Carbon;
/**
 * App\Models\Voucher
 *
 * @property int                                                                            $id
 * @property string                                                                         $type
 * @property string                                                                         $balance
 * @property int                                                                            $account_id
 * @property int|null                                                                       $enterprise_id
 * @property Carbon|null                                                $created_at
 * @property Carbon|null                                                $updated_at
 * @property-read Enterprise|null                                               $enterprise
 * @property-read Collection|VoucherTransaction[] $voucherTransaction
 * @property-read int|null                                                                  $voucher_transaction_count
 * @method static VoucherFactory factory(...$parameters)
 * @method static Builder|Voucher newModelQuery()
 * @method static Builder|Voucher newQuery()
 * @method static Builder|Voucher query()
 * @method static Builder|Voucher whereAccountId($value)
 * @method static Builder|Voucher whereBalance($value)
 * @method static Builder|Voucher whereCreatedAt($value)
 * @method static Builder|Voucher whereEnterpriseId($value)
 * @method static Builder|Voucher whereId($value)
 * @method static Builder|Voucher whereType($value)
 * @method static Builder|Voucher whereUpdatedAt($value)
 */
	class IdeHelperVoucher {}
}

namespace App\Models{use Database\Factories\VoucherTransactionFactory;use Illuminate\Database\Eloquent\Builder;use Illuminate\Support\Carbon;
/**
 * App\Models\VoucherTransaction
 *
 * @property int $id
 * @property string $type
 * @property string $amount
 * @property string $description
 * @property int $voucher_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Voucher $voucher
 * @method static VoucherTransactionFactory factory(...$parameters)
 * @method static Builder|VoucherTransaction newModelQuery()
 * @method static Builder|VoucherTransaction newQuery()
 * @method static Builder|VoucherTransaction query()
 * @method static Builder|VoucherTransaction whereAmount($value)
 * @method static Builder|VoucherTransaction whereCreatedAt($value)
 * @method static Builder|VoucherTransaction whereDescription($value)
 * @method static Builder|VoucherTransaction whereId($value)
 * @method static Builder|VoucherTransaction whereType($value)
 * @method static Builder|VoucherTransaction whereUpdatedAt($value)
 * @method static Builder|VoucherTransaction whereVoucherId($value)
 */
	class IdeHelperVoucherTransaction {}
}

