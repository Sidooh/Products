<?php

namespace App\Enums;

Enum Initiator: string
{
    case CONSUMER = 'CONSUMER';
    case AGENT = 'AGENT';
    case ENTERPRISE = "ENTERPRISE";
}
