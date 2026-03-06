<?php

declare(strict_types=1);

namespace Billing\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class SubscriptionPriceChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $gatewaySubscriptionId,
        public int $oldAmount,
        public int $newAmount,
        public string $effectiveAt
    ) {}
}
