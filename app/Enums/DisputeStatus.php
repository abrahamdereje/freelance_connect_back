<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case PENDING = 'pending';
    case RESOLVING = 'resolving';
    case RESOLVED = 'resolved';
    case REJECTED = 'rejected';
}
