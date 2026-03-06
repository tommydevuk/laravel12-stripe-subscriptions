<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

trait StripeSyncable
{
    /**
     * Execute a callback with Stripe syncing disabled.
     */
    public static function withoutStripeSync(callable $callback): mixed
    {
        $original = StripeSyncState::$disabled;
        StripeSyncState::$disabled = true;

        try {
            return $callback();
        } finally {
            StripeSyncState::$disabled = $original;
        }
    }
}
