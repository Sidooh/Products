<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

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
    public function rules(): array
    {
        return [
            'initiator'     => ['required', new Enum(Initiator::class)],
            'account_id'    => ['integer', Rule::requiredIf($this->is('*/products/airtime'))],
            "recipients_data" => ['array', Rule::requiredIf($this->is('*/products/airtime/bulk'))],
            'method'        => [new Enum(PaymentMethod::class),],
        ];
    }
}
