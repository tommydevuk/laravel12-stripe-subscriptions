<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

use Billing\Domain\Contracts\SubscriptionRepositoryInterface;
use Billing\Domain\Events\SubscriptionPaymentFailed;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Billing\Infrastructure\Persistence\CustomerModel;
use Billing\Infrastructure\Persistence\PriceModel;
use Billing\Infrastructure\Persistence\ProductModel;
use Illuminate\Support\Facades\Event;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessStripeWebhook extends ProcessWebhookJob
{
    public function handle(SubscriptionRepositoryInterface $repository): void
    {
        /** @var string $type */
        $type = $this->webhookCall->payload['type'];
        /** @var array<string, mixed> $data */
        $data = $this->webhookCall->payload['data']['object'];

        match ($type) {
            'customer.created', 'customer.updated' => $this->handleCustomerSync($data),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($data, $repository),
            'invoice.payment_succeeded', 'invoice.paid' => $this->handlePaymentSucceeded($data, $repository),
            'invoice.payment_failed' => $this->handlePaymentFailed($data, $repository),
            'product.created', 'product.updated' => $this->handleProductSync($data),
            'price.created', 'price.updated' => $this->handlePriceSync($data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleSubscriptionUpdated(array $data, SubscriptionRepositoryInterface $repository): void
    {
        $subscription = $repository->findByGatewayId($data['id']);

        if ($subscription) {
            $subscription = $subscription->updatePlan(
                $data['items']['data'][0]['plan']['id'],
                SubscriptionStatus::from($data['status'])
            );
            $repository->save($subscription);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handlePaymentSucceeded(array $data, SubscriptionRepositoryInterface $repository): void
    {
        if (! isset($data['subscription'])) {
            return;
        }

        $subscription = $repository->findByGatewayId($data['subscription']);

        if ($subscription) {
            // Update status to active
            $subscription = $subscription->updatePlan($subscription->planId, SubscriptionStatus::Active);
            $repository->save($subscription);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handlePaymentFailed(array $data, SubscriptionRepositoryInterface $repository): void
    {
        if (! isset($data['subscription'])) {
            return;
        }

        $subscription = $repository->findByGatewayId($data['subscription']);

        if ($subscription) {
            $subscription = $subscription->updatePlan($subscription->planId, SubscriptionStatus::PastDue);
            $repository->save($subscription);

            // Dispatch event for retry/notification
            Event::dispatch(new SubscriptionPaymentFailed(
                subscription: $subscription,
                reason: $data['billing_reason'] ?? 'Payment failed'
            ));
        }
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
    private function handleCustomerSync(array $data): void
    {
        CustomerModel::withoutStripeSync(function () use ($data): void {
            CustomerModel::updateOrCreate(
                ['stripe_id' => $data['id']],
                [
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
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
