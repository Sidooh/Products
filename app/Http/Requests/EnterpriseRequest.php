<?php

namespace App\Http\Requests;

use App\Enums\EnterpriseAccountType;
use App\Rules\SidoohAccountExists;
use Illuminate\Foundation\Http\FormRequest;
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
            "name"         => "required|string",
            "settings"     => "required|array",
            "account_id"   => ["bail", "required", "integer", new SidoohAccountExists],
            "account_type" => ["required", new Enum(EnterpriseAccountType::class)],
        ];
    }
}
