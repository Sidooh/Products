<?php

namespace App\Enums;

enum Status: string
{
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case PENDING = "PENDING";
    case REIMBURSED = "REIMBURSED";
}
