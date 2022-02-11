<?php

namespace App\Repositories;

use App\Enums\Description;
use App\Enums\TransactionType;
use App\Models\Enterprise;
use App\Models\Voucher;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Support\Facades\DB;
use Throwable;

class VoucherRepository
{
    use ApiResponse;

    /**
     * @throws Exception
     * @throws Throwable
     */
    public static function disburse(Enterprise $enterprise, array $disburseData)
    {
        DB::transaction(function() use ($enterprise, $disburseData) {
            $vouchers = Voucher::whereEnterpriseId($disburseData['enterprise_id'])
                ->whereIn('account_id', $disburseData['accounts'])
                ->whereType("ENTERPRISE_{$disburseData['disburse_type']}")
                ->get();

            if($vouchers->isEmpty()) return;

            $floatDebitAmount = $vouchers->sum('voucher_top_up_amount');

            if($enterprise->floatAccount->balance < $floatDebitAmount) throw new Exception('Insufficient float balance!', 422);

            $creditVouchers = $vouchers->map(function(Voucher $voucher) {
                return [
                    'type'          => $voucher->type,
                    'enterprise_id' => $voucher->enterprise_id,
                    'account_id'    => $voucher->account_id,
                    'balance'       => (double)$voucher->balance + (double)$voucher->voucher_top_up_amount,
                ];
            })->toArray();

            $enterprise->floatAccount->balance -= $floatDebitAmount;
            $enterprise->floatAccount->save();

            $enterprise->floatAccount->floatAccountTransaction()->create([
                'type'        => TransactionType::DEBIT,
                'amount'      => $floatDebitAmount,
                'description' => Description::VOUCHER_DISBURSEMENT
            ]);

            Voucher::upsert($creditVouchers, ['account_id', 'enterprise_id', 'type'], ['balance']);
        });
    }
}
