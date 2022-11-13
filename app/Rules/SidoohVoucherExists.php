<?php

namespace App\Rules;

use App\Services\SidoohPayments;
use Exception;
use Illuminate\Contracts\Validation\InvokableRule;

class SidoohVoucherExists implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail): void
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
