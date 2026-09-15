<?php

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case PendingVerification = 'pending_verification';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
