<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

use Billing\Application\DTOs\CreateSubscriptionDTO;
use Billing\Application\DTOs\SubscriptionResult;
use Billing\Domain\Contracts\PaymentGatewayInterface;
use Billing\Domain\Exceptions\PaymentFailedException;
use Billing\Domain\ValueObjects\SubscriptionStatus;
use Stripe\StripeClient;
use Throwable;

class StripePaymentGateway implements PaymentGatewayInterface
{
    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function subscribe(string $customerId, CreateSubscriptionDTO $dto): SubscriptionResult
    {
        try {
            $stripeSubscription = $this->stripe->subscriptions->create([
                'customer' => $customerId,
                'items' => [['plan' => $dto->planId]],
                'default_payment_method' => $dto->paymentMethodId,
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            return new SubscriptionResult(
                gatewaySubscriptionId: $stripeSubscription->id,
                status: SubscriptionStatus::from($stripeSubscription->status),
                rawPayload: $stripeSubscription->toArray()
            );
        } catch (Throwable $e) {
            throw new PaymentFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function updateSubscription(string $gatewaySubscriptionId, string $newPlanId): SubscriptionResult
    {
        try {
            $stripeSubscription = $this->stripe->subscriptions->retrieve($gatewaySubscriptionId);
            $stripeSubscription = $this->stripe->subscriptions->update($gatewaySubscriptionId, [
                'items' => [
                    [
                        'id' => $stripeSubscription->items->data[0]->id,
                        'plan' => $newPlanId,
                    ],
                ],
                'proration_behavior' => 'always_invoice',
                'expand' => ['latest_invoice.payment_intent'],
            ]);

            return new SubscriptionResult(
                gatewaySubscriptionId: $stripeSubscription->id,
                status: SubscriptionStatus::from($stripeSubscription->status),
                rawPayload: $stripeSubscription->toArray()
            );
        } catch (Throwable $e) {
            throw new PaymentFailedException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function refund(string $paymentIntentId, int $amount): void
    {
        try {
            $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => $amount,
            ]);
        } catch (Throwable $e) {
            throw new PaymentFailedException("Refund failed: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function retryPayment(string $invoiceId): SubscriptionResult
    {
        try {
            /** @var mixed $invoice */
            $invoice = $this->stripe->invoices->pay($invoiceId, [
                'expand' => ['subscription'],
            ]);

            /** @var \Stripe\Subscription $stripeSubscription */
            $stripeSubscription = $invoice->subscription;

            return new SubscriptionResult(
                gatewaySubscriptionId: $stripeSubscription->id,
                status: SubscriptionStatus::from($stripeSubscription->status),
                rawPayload: $stripeSubscription->toArray()
            );
        } catch (Throwable $e) {
            throw new PaymentFailedException("Payment retry failed: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
