<?php

declare(strict_types=1);

namespace Billing\Domain\Entities;

use Billing\Domain\ValueObjects\SubscriptionId;
use Billing\Domain\ValueObjects\SubscriptionStatus;

class Subscription
{
    public function __construct(
        public readonly ?SubscriptionId $id,
        public readonly string $customerId,
        public readonly string $planId,
        public SubscriptionStatus $status,
        public readonly string $gatewayId,
    ) {}

    public static function create(
        string $customerId,
        string $planId,
        SubscriptionStatus $status,
        string $gatewayId,
        ?string $id = null
    ): self {
        return new self(
            $id ? new SubscriptionId($id) : null,
            $customerId,
            $planId,
            $status,
            $gatewayId
        );
    }
}
