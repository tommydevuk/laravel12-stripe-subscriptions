<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
}
