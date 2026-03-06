<?php

declare(strict_types=1);

namespace Billing\Domain\Contracts;

interface PriceRepositoryInterface
{
    /**
     * @return array{amount: int, currency: string}
     */
    public function getPriceDetails(string $priceId): array;
}
