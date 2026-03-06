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
}
