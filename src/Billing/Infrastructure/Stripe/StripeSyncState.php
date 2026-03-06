<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

final class StripeSyncState
{
    /**
     * Indicates if Stripe syncing is currently disabled globally.
     */
    public static bool $disabled = false;
}
