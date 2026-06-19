<?php

namespace App\Enums;

enum JobStatus: string
{
    case DRAFT = 'draft';
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
