<?php

declare(strict_types=1);

namespace Billing\Application\Actions;

use Billing\Domain\Contracts\PaymentGatewayInterface;
use Billing\Domain\Contracts\SubscriptionRepositoryInterface;

final class RetryPayment
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly SubscriptionRepositoryInterface $repository,
    ) {}

    public function execute(string $invoiceId): void
    {
        // Gateway payment retry
        $result = $this->gateway->retryPayment($invoiceId);

        // Update local status if succeeded
        $subscription = $this->repository->findByGatewayId($result->gatewaySubscriptionId);

        if ($subscription) {
            $subscription = $subscription->updatePlan($subscription->planId, $result->status);
            $this->repository->save($subscription);
        }
    }
}
