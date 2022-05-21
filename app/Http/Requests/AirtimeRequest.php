<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use JetBrains\PhpStorm\ArrayShape;

class AirtimeRequest extends FormRequest
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
    #[ArrayShape([
        "amount"          => "string",
        "initiator"       => "array",
        "account_id"      => "string[]",
        "enterprise_id"   => "string[]",
        "recipients_data" => "array",
        "method"          => "\Illuminate\Validation\Rules\Enum[]",
        "target_number"   => "string",
        "debit_account"   => "\Illuminate\Validation\ConditionalRules"
    ])] public function rules(): array
    {
        $countryCode = config('services.sidooh.country_code');

        return [
            "amount"          => "required|integer",
            "initiator"       => ['required', new Enum(Initiator::class)],
            "account_id"      => ['integer', "required"],
            "enterprise_id"   => ["required_if:initiator," . Initiator::ENTERPRISE->name],
            "recipients_data" => ['array', Rule::requiredIf($this->is('*/products/airtime/bulk'))],
            "method"          => [new Enum(PaymentMethod::class)],
            "target_number"   => "phone:$countryCode",
            "debit_account"   => Rule::when($this->input("method") === PaymentMethod::MPESA->value, "phone:$countryCode", "integer")
        ];
    }
}
