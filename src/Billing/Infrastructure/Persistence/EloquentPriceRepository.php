<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Billing\Domain\Contracts\PriceRepositoryInterface;
use Billing\Domain\Exceptions\SubscriptionException;

final class EloquentPriceRepository implements PriceRepositoryInterface
{
    public function getPriceDetails(string $priceId): array
    {
        $price = PriceModel::where('stripe_id', $priceId)->first();

        if (! $price) {
            throw new SubscriptionException("Price with Stripe ID {$priceId} not found locally.");
        }

        return [
            'amount' => $price->amount,
            'currency' => $price->currency,
        ];
    }
}
