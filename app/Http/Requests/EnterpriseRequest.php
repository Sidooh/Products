<?php

namespace App\Http\Requests;

use App\Enums\EnterpriseAccountType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EnterpriseRequest extends FormRequest
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
            'name'          => [Rule::requiredIf($this->is('*/enterprises'))],
            'settings'      => 'required|array',
            'accounts.id'   => 'integer',
            'accounts.type' => new Enum(EnterpriseAccountType::class),
            'enterprise_id' => ['exists:enterprises,id']
        ];
    }
}
