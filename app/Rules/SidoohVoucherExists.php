<?php

namespace App\Rules;

use App\Services\SidoohPayments;
use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;

class SidoohVoucherExists implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $voucher = SidoohPayments::findVoucher($value);

            if (! isset($voucher['id'])) {
                $fail('The :attribute must be a valid Sidooh voucher.');
            }
        } catch (Exception) {
            $fail('The :attribute must be a valid Sidooh voucher.');
        }
    }
}
