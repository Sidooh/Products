<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\SidoohAccounts;
use App\Services\SidoohNotify;
use Exception;
use NumberFormatter;

class SidoohEventRepository extends EventRepository
{
    /**
     * @throws Exception
     */
    public static function subscriptionPurchaseSuccess(Subscription $subscription, Transaction $transaction)
    {
        $type = $subscription->subscriptionType;
        $account = SidoohAccounts::find($transaction->account_id);
        $phone = ltrim($account['phone'], '+');

        $date = $subscription->created_at->timezone('Africa/Nairobi')->format(config("settings.sms_date_time_format"));
        $end_date = $subscription->created_at->addMonths($subscription->subscriptionType->duration)
            ->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        $nf = new NumberFormatter('en', NumberFormatter::ORDINAL);
        $limit = $nf->format($type->level_limit);

        switch ($type->duration) {
            case 1:
                $message = "Congratulations! You have successfully registered as a $type->title on $date, valid until $end_date. ";
                $message .= "You will earn commissions on every airtime purchased by your referred customers and sub-agents up to your $limit ripple.\n";
                break;
            default:
                $level_duration = $type->duration . " MONTHS";
                $message = "Congratulations! You have successfully pre-registered as a $type->title on $date, valid until $end_date. ";
                $message .= "You will earn commissions on every airtime purchased by your referred customers and sub-agents up to your ";
                $message .= "$limit ripple, for $level_duration WITHOUT PAYING MONTHLY SUBSCRIPTION FEES.\n";
        }

        $message .= config('services.sidooh.tagline');

        SidoohNotify::notify([$phone], $message, EventType::SUBSCRIPTION_PAYMENT);
    }

    /**
     * @throws Exception
     */
    public static function voucherPurchaseSuccess(Transaction $transaction, array $vouchers, array $payment)
    {
        $amount = 'Ksh' . number_format($transaction->amount, 2);
        $account = SidoohAccounts::find($transaction->account_id);
        $date = $transaction->updated_at
            ->timezone('Africa/Nairobi')
            ->format(config("settings.sms_date_time_format"));

        // Ensure a couple of things are valid first
        // 1. payment matches transaction
        if ($payment['payable_type'] !== 'TRANSACTION' || $payment['payable_id'] !== $transaction->id) {
            throw new Exception('Payment does not match transaction');
        }

        // 2. Vouchers (if many) match accounts in question
        $voucherLen = count($vouchers);
        if ($voucherLen === 1) {
            // Purchase was for self most probably.
            // Can confirm this using transaction account and destination

            $creditVoucher = $vouchers[0];
            if ($creditVoucher['account_id'] !== $transaction->account_id) {

                // Check Purchasing for other using MPESA
                $accountFor = SidoohAccounts::findByPhone($transaction->destination);

                if ($accountFor['id'] === $creditVoucher['account_id']) {
                    // Send to purchaser
                    $phone = $account['phone'];

                    $message = "You have purchased $amount voucher ";
                    $message .= "for $transaction->destination on $date.\n\n";
                    $message .= config('services.sidooh.tagline');

                    SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);

                    // Send to purchasee
                    $phone = $accountFor['phone'];
                    $balance = 'Ksh' . number_format($creditVoucher['balance'], 2);

                    $message = "You have received $amount voucher ";
                    $message .= "from Sidooh account {$account['phone']} on $date.\n";
                    $message .= "New voucher balance is $balance.\n\n";
                    $message .= "Dial *384*99# NOW for FREE on your Safaricom line to BUY AIRTIME or TOKENS & PAY USING the voucher received.\n\n";
                    $message .= config('services.sidooh.tagline');

                    SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);

                    return;
                }

                throw new Exception('Voucher does not match account making transaction');

            }

            // select voucher
            $debitVoucher = $vouchers[0];

            // send notification
            $phone = $account['phone'];
            $balance = 'Ksh' . number_format($debitVoucher['balance'], 2);

            $message = "Congratulations! You have successfully topped up your voucher ";
            $message .= "with $amount on $date.\n";
            $message .= "New voucher balance is $balance.\n\n";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);

        } else if ($voucherLen == 2) {
            // Purchase was for other
            // Confirm other voucher is valid using transaction destination

            // select vouchers
            $accountFor = SidoohAccounts::findByPhone($transaction->destination);

            if ($vouchers[0]['account_id'] == $account['id'] && $vouchers[1]['account_id'] == $accountFor['id']) {
                $debitVoucher = $vouchers[0];
                $creditVoucher = $vouchers[1];
            } else if ($vouchers[1]['account_id'] == $account['id'] && $vouchers[0]['account_id'] == $accountFor['id']) {
                $debitVoucher = $vouchers[1];
                $creditVoucher = $vouchers[0];
            } else {
                throw new Exception('Voucher mismatch with accounts');
            }


            // send notification self
            $phone = $account['phone'];
            $balance = 'Ksh' . number_format($debitVoucher['balance'], 2);

            $message = "You have purchased $amount voucher ";
            $message .= "for $transaction->destination on $date.\n";
            $message .= "New voucher balance is $balance.\n\n";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);


            // send notification target

            $phone = $accountFor['phone'];
            $balance = 'Ksh' . number_format($creditVoucher['balance'], 2);

            $message = "You have received $amount voucher ";
            $message .= "from Sidooh account {$account['phone']} on $date.\n";
            $message .= "New voucher balance is $balance.\n\n";
            $message .= "Dial *384*99# NOW for FREE on your Safaricom line to BUY AIRTIME or TOKENS & PAY USING the voucher received.\n\n";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);

        }
    }

}
