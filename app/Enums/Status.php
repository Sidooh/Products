<?php

namespace App\Enums;

enum Status: string
{
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case PENDING = 'PENDING';
    case REFUNDED = 'REFUNDED';

    case ACTIVE = 'ACTIVE';

    const EXPIRED = 'EXPIRED';

    // others
//    const RECONCILED,
//    const REASSIGNED,
//    const ALLOCATED,
//    const SENT,
//    const SUCCESS,
}
