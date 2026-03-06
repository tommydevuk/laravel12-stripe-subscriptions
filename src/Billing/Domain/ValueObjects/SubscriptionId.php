<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

use InvalidArgumentException;

readonly class SubscriptionId
{
    public function __construct(public string $value)
    {
        if (empty($this->value)) {
            throw new InvalidArgumentException('Subscription ID cannot be empty.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
