<?php

namespace App\Enums;

Enum Status: string
{
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case PENDING = "PENDING";
    case REIMBURSED = "REIMBURSED";
}
