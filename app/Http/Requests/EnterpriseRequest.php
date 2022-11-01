<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JetBrains\PhpStorm\ArrayShape;

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
    #[ArrayShape(['name' => 'array', 'settings' => 'string', 'accounts' => 'string', 'enterprise_id' => 'string[]'])]
    public function rules(): array
    {
        return [
            'name' => [Rule::requiredIf($this->is('*/enterprises'))],
            'settings' => 'array',
            'accounts' => 'array',
            'enterprise_id' => ['exists:enterprises,id'],
        ];
    }
}
