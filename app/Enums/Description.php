<?php

namespace App\Enums;

enum Description: string
{
    case AIRTIME_PURCHASE = 'Airtime Purchase';
    case UTILITY_PURCHASE = 'Utility Purchase';

    case VOUCHER_DISBURSEMENT = 'Voucher Disbursement';
    case VOUCHER_PURCHASE = 'Voucher Purchase';
}
