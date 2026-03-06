<?php

declare(strict_types=1);

namespace Billing\Application\DTOs;

final readonly class UpdateSubscriptionDTO
{
    public function __construct(
        public string $gatewaySubscriptionId,
        public string $newPlanId,
    ) {}
}
