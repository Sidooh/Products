<?php

namespace App\Enums;

enum VoucherTransactionType: string
{
    case CREDIT = "CREDIT";
    case DEBIT = "DEBIT";
}
