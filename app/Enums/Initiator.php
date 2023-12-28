<?php

namespace App\Enums;

enum Initiator: string
{
    case CONSUMER = 'CONSUMER';
    case MERCHANT = 'MERCHANT';
    case ENTERPRISE = 'ENTERPRISE';
}
