<?php

namespace App\Http\Requests;

use App\Enums\Initiator;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rules\Enum;

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
            'product'    => 'required|in:airtime,utility,subscription,voucher',
            'initiator'  => ['required', new Enum(Initiator::class)],
            'amount'     => 'required|numeric',
            'account_id' => 'numeric',
            'phone'      => 'phone:KE',
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
