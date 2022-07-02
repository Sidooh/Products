<?php

namespace App\Enums;

enum EarningAccountType
{
    case PURCHASES;
    case SUBSCRIPTIONS;
    case WITHDRAWALS;
    case MERCHANT;
}
