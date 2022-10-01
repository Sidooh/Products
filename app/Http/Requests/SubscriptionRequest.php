<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
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
            'initiator'            => ['required', new Enum(Initiator::class)],
            'account_id'           => 'integer',
            'method'               => [new Enum(PaymentMethod::class)],
            'account_number'       => [Rule::requiredIf($this->is('*/products/utility')), 'integer'],
            'utility_provider'     => ['required_if:product,utility'],
            'target_number'        => 'phone:KE',
            'debit_account'        => 'phone:KE',
            'subscription_type_id' => 'required|exists:subscription_types,id',
        ];
    }
}
