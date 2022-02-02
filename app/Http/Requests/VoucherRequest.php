<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class VoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'initiator'  => ['required', new Enum(Initiator::class)],
            'account_id' => 'integer',
            'amount'     => ['required', 'numeric',],
        ];
    }
}
