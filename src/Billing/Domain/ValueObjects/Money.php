<?php

declare(strict_types=1);

namespace Billing\Domain\ValueObjects;

readonly class Money
{
    public function __construct(
        public int $amount,
        public string $currency = 'usd'
    ) {}

    public function formatted(): string
    {
        return number_format($this->amount / 100, 2).' '.strtoupper($this->currency);
    }
}
