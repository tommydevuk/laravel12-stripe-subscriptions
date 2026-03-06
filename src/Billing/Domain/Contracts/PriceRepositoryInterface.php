<?php

declare(strict_types=1);

namespace Billing\Domain\Contracts;

use Billing\Domain\ValueObjects\Money;

interface PriceRepositoryInterface
{
    /**
     * @return array{amount: int, currency: string}
     */
    public function getPriceDetails(string $priceId): array;
}
