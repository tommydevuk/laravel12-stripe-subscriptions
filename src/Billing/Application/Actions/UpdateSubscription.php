<?php

declare(strict_types=1);

namespace Billing\Application\Actions;

use Billing\Application\DTOs\UpdateSubscriptionDTO;
use Billing\Domain\Contracts\PaymentGatewayInterface;
use Billing\Domain\Contracts\PriceRepositoryInterface;
use Billing\Domain\Contracts\SubscriptionRepositoryInterface;
use Billing\Domain\Events\SubscriptionPriceChanged;
use Billing\Domain\Exceptions\SubscriptionException;
use Illuminate\Support\Facades\Event;

final class UpdateSubscription
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PriceRepositoryInterface $priceRepository,
    ) {}

    public function execute(UpdateSubscriptionDTO $dto): void
    {
        $subscription = $this->subscriptionRepository->findByGatewayId($dto->gatewaySubscriptionId);

        if (! $subscription) {
            throw new SubscriptionException('Subscription not found.');
        }

        $oldPrice = $this->priceRepository->getPriceDetails($subscription->planId);
        $newPrice = $this->priceRepository->getPriceDetails($dto->newPlanId);

        // Update at gateway
        $result = $this->gateway->updateSubscription($dto->gatewaySubscriptionId, $dto->newPlanId);

        // Handle partial refund if price decreased
        if ($newPrice['amount'] < $oldPrice['amount']) {
            $difference = $oldPrice['amount'] - $newPrice['amount'];

            // Get last payment intent if available to issue refund
            $paymentIntentId = $result->rawPayload['latest_invoice']['payment_intent']['id'] ?? null;

            if ($paymentIntentId) {
                $this->gateway->refund($paymentIntentId, $difference);
            }
        }

        // Save local state
        $subscription = $subscription->updatePlan($dto->newPlanId, $result->status);
        $this->subscriptionRepository->save($subscription);

        // Notify user (via event)
        Event::dispatch(new SubscriptionPriceChanged(
            gatewaySubscriptionId: $subscription->gatewayId,
            oldAmount: $oldPrice['amount'],
            newAmount: $newPrice['amount'],
            effectiveAt: now()->toDateTimeString(),
        ));
    }
}
