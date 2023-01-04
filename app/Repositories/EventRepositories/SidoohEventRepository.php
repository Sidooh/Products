<?php

namespace App\Repositories\EventRepositories;

use App\Enums\EventType;
use App\Enums\PaymentMethod;
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

        $date = $subscription->created_at->timezone('Africa/Nairobi')->format(config('settings.sms_date_time_format'));
        $end_date = $subscription->created_at->addMonths($subscription->subscriptionType->duration)
            ->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        $nf = new NumberFormatter('en', NumberFormatter::ORDINAL);
        $limit = $nf->format($type->level_limit);
//        $title = $type->title;
        $title = 'Earn More'; // TODO: Remove

        switch ($type->duration) {
            case 1:
                $message = "Congrats! You have successfully subscribed to $title on $date, valid until $end_date. ";
                $message .= "You will get (1) HIGHER POINTS on ALL your purchases and payments, and (2) EXTRA POINTS on ALL purchases, payments and subscriptions done by your invited friends up to your $limit ripple.\n";
                break;
            default:
                $level_duration = $type->duration.' MONTHS';
                $message = "Congratulations! You have successfully pre-registered as a $title on $date, valid until $end_date. ";
                $message .= 'You will earn commissions on airtime and utilities purchased by your invited friends and sub-agents up to your ';
                $message .= "$limit ripple, for $level_duration WITHOUT PAYING MONTHLY SUBSCRIPTION FEES.\n";
        }

        $message .= config('services.sidooh.tagline');

        SidoohNotify::notify([$phone], $message, EventType::SUBSCRIPTION_PAYMENT);
    }

    /**
     * @throws Exception
     */
    public static function voucherPurchaseSuccess(Transaction $transaction, array $vouchers)
    {
        $amount = 'Ksh'.number_format($transaction->amount, 2);
        $account = SidoohAccounts::find($transaction->account_id);
        $date = $transaction->updated_at
            ->timezone('Africa/Nairobi')
            ->format(config('settings.sms_date_time_format'));

        if ($transaction->payment->subtype === PaymentMethod::VOUCHER->name) {
            $method = PaymentMethod::VOUCHER->name;

            $voucher = $transaction->payment->extra;
            $bal = 'Ksh'.number_format($voucher['balance'], 2);
            $vtext = "\nNew voucher balance is $bal.";
        } else {
            $method = $transaction->payment->type;
            $vtext = '';

            $extra = $transaction->payment->extra;
            if (isset($extra['debit_account']) && $account['phone'] !== $extra['debit_account']) {
                $method = 'OTHER '.$method;
            }
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
                    $message .= "for $transaction->destination on $date using $method.$vtext\n\n";
                    $message .= config('services.sidooh.tagline');

                    SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);

                    // Send to purchasee
                    $phone = $accountFor['phone'];
                    $balance = 'Ksh'.number_format($creditVoucher['balance'], 2);

                    $message = "You have received $amount voucher ";
                    $message .= "from Sidooh account {$account['phone']} on $date.\n";
                    $message .= "New voucher balance is $balance.\n\n";
                    $message .= "Dial *384*99# NOW for FREE on your Safaricom line to BUY AIRTIME or PAY BILLS & PAY USING the voucher received.\n\n";
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
            $balance = 'Ksh'.number_format($debitVoucher['balance'], 2);

            $message = 'Congratulations! You have successfully topped up your voucher ';
            $message .= "with $amount on $date using $method.$vtext\n";
            $message .= "New voucher balance is $balance.\n\n";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);
        } elseif ($voucherLen == 2) {
            // Purchase was for other
            // Confirm other voucher is valid using transaction destination

            // select vouchers
            $accountFor = SidoohAccounts::findByPhone($transaction->destination);

            if ($vouchers[0]['account_id'] == $account['id'] && $vouchers[1]['account_id'] == $accountFor['id']) {
                $creditVoucher = $vouchers[1];
            } elseif ($vouchers[1]['account_id'] == $account['id'] && $vouchers[0]['account_id'] == $accountFor['id']) {
                $creditVoucher = $vouchers[0];
            } else {
                throw new Exception('Voucher mismatch with accounts');
            }

            // send notification self
            $phone = $account['phone'];

            $message = "You have purchased $amount voucher ";
            $message .= "for $transaction->destination on $date using $method.$vtext\n\n";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);

            // send notification target
            $phone = $accountFor['phone'];
            $balance = 'Ksh'.number_format($creditVoucher['balance'], 2);

            $message = "You have received $amount voucher ";
            $message .= "from Sidooh account {$account['phone']} on $date.\n";
            $message .= "New voucher balance is $balance.\n\n";
            $message .= "Dial *384*99# NOW for FREE on your Safaricom line to BUY AIRTIME or PAY BILLS & PAY USING the voucher received.\n\n";
            $message .= config('services.sidooh.tagline');

            SidoohNotify::notify([$phone], $message, EventType::VOUCHER_PURCHASE);
        }
    }
}
