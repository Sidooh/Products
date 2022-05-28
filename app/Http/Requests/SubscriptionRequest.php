<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use App\Models\SubscriptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'initiator'        => ['required', new Enum(Initiator::class)],
            'account_id'       => 'integer',
            'amount'           => [
                'required',
                'numeric',
                function($attribute, $value, $fail) {
                    $subPrices = SubscriptionType::pluck('price')->toArray();
                    $subPricesStr = implode(', ',$subPrices);

                    if(!in_array($value, $subPrices)) {
                        $fail("The $attribute must be either of: {$subPricesStr}.");
                    }
                },
            ],
            'method'           => [
                "required_if:initiator,CONSUMER",
                new Enum(PaymentMethod::class),
            ],
            'account_number'   => [Rule::requiredIf($this->is('*/products/utility')), 'integer'],
            'utility_provider' => ["required_if:product,utility"],
            'target_number'    => 'phone:KE',
            'debit_account'     => 'phone:KE',
        ];
    }
}
