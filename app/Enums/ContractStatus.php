<?php

namespace App\Enums;

enum ContractStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case DISPUTED = 'disputed';
    case TERMINATED = 'terminated';
}
