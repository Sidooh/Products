<?php

namespace App\Enums;

enum EarningCategory: string
{
    //self is a reserved keyword so will be reformatted to lowercase: psr-12 lowercase_static_reference
    case SELF_EARNING = 'SELF';
    case INVITE = 'INVITE';
    case SYSTEM = 'SYSTEM';
}
