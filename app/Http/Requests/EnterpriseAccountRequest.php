<?php

namespace App\Http\Requests;

use App\Enums\EnterpriseAccountType;
use App\Rules\SidoohAccountExists;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EnterpriseAccountRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'account_id' => [
                'required',
                'integer',
                new SidoohAccountExists,
                Rule::unique('enterprise_accounts')->where('enterprise_id', $this->route('enterprise')?->id)
                    ->where('account_id', $this->input('account_id')),
            ],
            'type'       => ['required', new Enum(EnterpriseAccountType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'account_id' => [
                'unique' => 'Account already belongs to enterprise.',
            ],
        ];
    }
}
