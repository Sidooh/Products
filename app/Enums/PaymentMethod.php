<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case MPESA = 'MPESA';
    case VOUCHER = 'VOUCHER';
    case SIDOOH_POINTS = 'SIDOOH_POINTS';
}
