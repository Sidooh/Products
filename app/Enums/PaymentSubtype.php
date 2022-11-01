<?php

namespace App\Enums;

enum PaymentSubtype: string
{
    case STK = 'STK';
    case C2B = 'C2B';
    case CBA = 'CBA';
    case WALLET = 'WALLET';
    case VOUCHER = 'VOUCHER';
    case BONUS = 'BONUS';
}
