<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use JetBrains\PhpStorm\ArrayShape;

class VoucherRequest extends FormRequest
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
            'initiator'     => [
                Rule::requiredIf(!$this->is('*/products/voucher/disburse')),
                new Enum(Initiator::class),
                function($attribute, $value, $fail) {
                    $isVoucherDisburse = $this->is('*/products/voucher/disburse');

                    if($isVoucherDisburse && $value !== "ENTERPRISE") $fail("Unauthorized Initiator!");
                },
            ],
            'disburse_type' => ['in:LUNCH,GENERAL',],
            'account_id'    => 'integer',
            'enterprise_id' => ['required_if:initiator,ENTERPRISE', 'exists:enterprises,id'],
            'amount'        => ['required_unless:initiator,null', 'numeric'],
            'accounts'      => ['array'],
            "method"          => [new Enum(PaymentMethod::class)],
            "target_number"   => "phone:$countryCode",
            'debit_account' => 'integer',
        ];
    }

    #[ArrayShape(['disburse_type.in' => "string"])]
    public function messages(): array
    {
        return [
            'disburse_type.in' => 'invalid :attribute. allowed values are: [LUNCH, GENERAL]'
        ];
    }
}
