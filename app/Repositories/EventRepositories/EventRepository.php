<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Enums\VoucherType;
use App\Events\TransactionSuccessEvent;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\Voucher;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;

class EventRepository
{
    /**
     * @throws Exception
     */
    public static function merchantPurchaseSuccess(Transaction $transaction, Merchant $merchant)
    {
        $amount = $transaction->amount;
        $account = SidoohAccounts::find($transaction->account_id);
        $voucher = Voucher::whereAccountId($account['id'])->whereType(VoucherType::SIDOOH)->firstOrFail();

        $phone = ltrim($account['phone'], '+');
        $mPhone = ltrim($merchant->contact_phone, '+');

        $date = $transaction->updated_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));

        $message = "SIDOOH transaction confirmed. Ksh{$amount} paid to {$merchant->name}";
        $message .= " on {$date}. New VOUCHER balance is {$voucher->balance}.\n\n";
        $message .= "Sidooh, Earns you money on every purchase.";

        SidoohNotify::notify([$phone], $message, EventType::MERCHANT_PAYMENT);

        $message = "SIDOOH transaction confirmed. You have received Ksh{$amount} from \$account->user->name' {$phone}";
        $message .= " on {$date}. New Account balance is {$merchant->balance}.\n\n";
        $message .= "Sidooh, Earns you money on every purchase.";

        SidoohNotify::notify([$mPhone], $message, EventType::MERCHANT_PAYMENT);

        $amount = min($transaction->amount * .025, 250);

        TransactionSuccessEvent::dispatch($transaction, $amount);
    }

}
