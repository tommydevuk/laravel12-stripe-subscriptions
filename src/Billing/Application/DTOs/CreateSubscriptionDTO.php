<?php

declare(strict_types=1);

namespace Billing\Application\DTOs;

readonly class CreateSubscriptionDTO
{
    public function __construct(
        public string $customerId,
        public string $planId,
        public string $paymentMethodId,
    ) {}
}
