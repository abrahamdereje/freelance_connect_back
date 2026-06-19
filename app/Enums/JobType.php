<?php

namespace App\Enums;

enum JobType: string
{
    case FIXED = 'fixed';
    case HOURLY = 'hourly';
}
