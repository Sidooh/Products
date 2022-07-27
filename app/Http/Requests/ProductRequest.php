<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
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
        $countryCode = config('services.sidooh.country_code');

        return [
            'initiator' => ['required', new Enum(Initiator::class)],
            'account_id' => 'integer',
            'amount' => ["required", 'numeric', 'min:20', 'max:10000',],
            'method' => [new Enum(PaymentMethod::class),],
            'enterprise_id' => ["required_if:initiator," . Initiator::ENTERPRISE->name],
            'account_number' => [Rule::requiredIf($this->is('*/products/utility')), 'numeric'],
            'provider' => [Rule::requiredIf($this->is('*/products/utility'))],
            'target_number' => "phone:$countryCode",
            'debit_account' => "phone:$countryCode",
        ];
    }

    #[ArrayShape(['product.in' => "string", 'debit_account.phone' => "string", 'target_number.phone' => "string"])]
    public function messages(): array
    {
        return [
            'debit_account.phone' => 'Invalid :attribute number',
            'target_number.phone' => 'Invalid target phone number',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'data'    => $validator->errors()->all()
        ], 422));
    }
}
