<?php

declare(strict_types=1);

namespace Billing\Application\DTOs;

use Billing\Domain\ValueObjects\SubscriptionStatus;

readonly class SubscriptionResult
{
    /**
     * @param  array<string, mixed>  $rawPayload
     */
    public function __construct(
        public string $gatewaySubscriptionId,
        public SubscriptionStatus $status,
        public array $rawPayload = []
    ) {}
}
