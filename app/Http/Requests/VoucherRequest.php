<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use App\Rules\SidoohAccountExists;
use App\Rules\SidoohVoucherExists;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
            'initiator' => [
                Rule::requiredIf(! $this->is('*/products/voucher/disburse')),
                new Enum(Initiator::class),
            ],
            'account_id'    => ['required', 'integer', new SidoohAccountExists],
            'amount'        => ['required', 'integer'],
            'method'        => ['exclude_without:target_number', new Enum(PaymentMethod::class)],
            'target_number' => [
                Rule::requiredIf($this->input('method') === PaymentMethod::VOUCHER->value),
                "phone:$countryCode",
            ],
//            'debit_account' => [
//                Rule::excludeIf($this->input('method') === PaymentMethod::VOUCHER->value),
//                "phone:$countryCode",
//            ],
            'debit_account' => [$this->sourceAccountRule()],
        ];
    }

    function sourceAccountRule(): InvokableRule|string
    {
        $countryCode = config('services.sidooh.country_code');

        return match (PaymentMethod::tryFrom($this->input('method'))) {
            PaymentMethod::MPESA => "phone:$countryCode",
            PaymentMethod::VOUCHER => new SidoohVoucherExists,
            default => abort(422, 'Unsupported source')
        };
    }
}
