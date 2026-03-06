<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

use Billing\Infrastructure\Persistence\PriceModel;
use Billing\Infrastructure\Persistence\ProductModel;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessStripeWebhook extends ProcessWebhookJob
{
    public function handle(): void
    {
        /** @var string $type */
        $type = $this->webhookCall->payload['type'];
        /** @var array<string, mixed> $data */
        $data = $this->webhookCall->payload['data']['object'];

        match ($type) {
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($data),
            'product.created', 'product.updated' => $this->handleProductSync($data),
            'price.created', 'price.updated' => $this->handlePriceSync($data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionUpdated(array $data): void
    {
        // For brevity, we'll just handle status changes via the repository or another action
        // In a real app, you might have an UpdateSubscription action
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleProductSync(array $data): void
    {
        ProductModel::withoutStripeSync(function () use ($data): void {
            ProductModel::updateOrCreate(
                ['stripe_id' => $data['id']],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'active' => (bool) $data['active'],
                    'metadata' => $data['metadata'] ?? [],
                ]
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handlePriceSync(array $data): void
    {
        PriceModel::withoutStripeSync(function () use ($data): void {
            $product = ProductModel::where('stripe_id', $data['product'])->first();

            if (! $product) {
                throw new \Exception("Product with stripe_id {$data['product']} not found. Retrying...");
            }

            PriceModel::updateOrCreate(
                ['stripe_id' => $data['id']],
                [
                    'product_id' => $product->id,
                    'amount' => $data['unit_amount'],
                    'currency' => $data['currency'],
                    'type' => $data['type'],
                    'interval' => $data['recurring']['interval'] ?? null,
                    'active' => (bool) $data['active'],
                    'metadata' => $data['metadata'] ?? [],
                ]
            );
        });
    }
}
