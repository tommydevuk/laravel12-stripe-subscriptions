<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe\Observers;

use Billing\Infrastructure\Persistence\PriceModel;
use Billing\Infrastructure\Stripe\StripeSyncable;
use Billing\Infrastructure\Stripe\StripeSyncState;
use Stripe\StripeClient;
use Throwable;

final class PriceObserver
{
    use StripeSyncable;

    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function created(PriceModel $price): void
    {
        if (StripeSyncState::$disabled) {
            return;
        }

        try {
            $params = [
                'product' => $price->product->stripe_id,
                'unit_amount' => $price->amount,
                'currency' => $price->currency,
                'active' => $price->active,
                'metadata' => $price->metadata,
            ];

            if ($price->type === 'recurring') {
                $params['recurring'] = ['interval' => $price->interval];
            }

            $stripePrice = $this->stripe->prices->create($params);

            PriceModel::withoutStripeSync(fn () => $price->update(['stripe_id' => $stripePrice->id]));
        } catch (Throwable) {
            // Handle or log error
        }
    }

    public function updated(PriceModel $price): void
    {
        if (StripeSyncState::$disabled || empty($price->stripe_id)) {
            return;
        }

        try {
            $this->stripe->prices->update($price->stripe_id, [
                'active' => $price->active,
                'metadata' => $price->metadata,
            ]);
        } catch (Throwable) {
            // Handle or log error
        }
    }
}
