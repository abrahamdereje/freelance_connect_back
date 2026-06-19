<?php

namespace App\Enums;

enum MilestoneStatus: string
{
    case PENDING = 'pending';
    case FUNDED = 'funded';
    case IN_REVIEW = 'in_review';
    case RELEASED = 'released';
    case REFUNDED = 'refunded';
}
