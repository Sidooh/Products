<?php

namespace App\Enums;

enum EarningAccountType: string
{
    case PURCHASES = 'PURCHASES';
    case SUBSCRIPTIONS = 'SUBSCRIPTIONS';
    case WITHDRAWALS = 'WITHDRAWALS';
    case MERCHANT = 'MERCHANT';
}
