<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\MerchantType;
use App\Enums\PaymentMethod;
use App\Rules\SidoohAccountExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class MerchantRequest extends FormRequest
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
            'amount'          => 'required|integer',
            'initiator'       => ['required', new Enum(Initiator::class)],
            'account_id'      => ['integer', 'required'],
            'method'          => [new Enum(PaymentMethod::class)],
            'merchant_type'   => [new Enum(MerchantType::class)],
            'business_number' => [
                Rule::requiredIf(
                    $this->input('merchant_type') === MerchantType::MPESA_PAY_BILL->name,
                ), ],
            'account_number'  => [
                Rule::requiredIf(
                    $this->input('merchant_type') === MerchantType::MPESA_PAY_BILL->name,
                ), ],
            'debit_account'   => [Rule::when(
                $this->input('method') === PaymentMethod::MPESA->value,
                "phone:$countryCode",
                [new SidoohAccountExists]
            )],
        ];
    }
}
