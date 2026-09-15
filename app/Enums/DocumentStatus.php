<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case ClosedShort = 'closed_short';
}
