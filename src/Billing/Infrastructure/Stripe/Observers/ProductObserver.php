<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe\Observers;

use Billing\Infrastructure\Persistence\ProductModel;
use Billing\Infrastructure\Stripe\StripeSyncable;
use Billing\Infrastructure\Stripe\StripeSyncState;
use Stripe\StripeClient;
use Throwable;

final class ProductObserver
{
    use StripeSyncable;

    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function created(ProductModel $product): void
    {
        if (StripeSyncState::$disabled) {
            return;
        }

        try {
            $stripeProduct = $this->stripe->products->create([
                'name' => $product->name,
                'description' => $product->description,
                'active' => $product->active,
                'metadata' => $product->metadata,
            ]);

            ProductModel::withoutStripeSync(fn () => $product->update(['stripe_id' => $stripeProduct->id]));
        } catch (Throwable) {
            // Handle or log error
        }
    }

    public function updated(ProductModel $product): void
    {
        if (StripeSyncState::$disabled || empty($product->stripe_id)) {
            return;
        }

        try {
            $this->stripe->products->update($product->stripe_id, [
                'name' => $product->name,
                'description' => $product->description,
                'active' => $product->active,
                'metadata' => $product->metadata,
            ]);
        } catch (Throwable) {
            // Handle or log error
        }
    }
}
