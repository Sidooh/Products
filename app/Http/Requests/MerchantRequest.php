<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use Illuminate\Foundation\Http\FormRequest;
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
        return [
            'initiator' => ['required', new Enum(Initiator::class)],
            'account_id' => ['required'],
            'amount' => ['required'],
            'merchant_code' => ['required', 'exists:merchants,code'],
        ];
    }
}
