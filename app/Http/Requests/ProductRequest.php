<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use App\Models\SubscriptionType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use JetBrains\PhpStorm\ArrayShape;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'initiator'        => ['required', new Enum(Initiator::class)],
            'account_id'       => 'integer',
            'amount'           => [
                'required',
                'numeric',
                function($attribute, $value, $fail) {
                    $isSubscription = $this->is('*/products/subscription');
                    $subPrices = SubscriptionType::pluck('price')->toArray();
                    $subPricesStr = implode(', ', $subPrices);

                    if($isSubscription && !in_array($value, $subPrices)) {
                        $fail("The $attribute must be either of: {$subPricesStr}.");
                    }
                },
            ],
            'method'           => [
                "required_if:initiator,CONSUMER",
                new Enum(PaymentMethod::class),
            ],
            'enterprise_id'    => ['required_if:initiator,ENTERPRISE'],
            'account_number'   => [Rule::requiredIf($this->is('*/products/utility')), 'integer'],
            'utility_provider' => ["required_if:product,utility"],
            'target_number'    => 'phone:KE',
            'mpesa_number'     => 'phone:KE',
        ];
    }

    #[ArrayShape(['product.in' => "string"])]
    public function messages(): array
    {
        return [
            'product.in' => 'Invalid product. Allowed product values are: [airtime, utility, subscription, voucher]',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'data'    => $validator->errors()->all()
            ], 422)
        );
    }
}
