<?php

namespace App\Enums;

enum TransactionType: string
{
    case PAYMENT = 'PAYMENT';
    case WITHDRAWAL = 'WITHDRAWAL';
}
