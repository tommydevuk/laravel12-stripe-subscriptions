<?php

declare(strict_types=1);

namespace Billing\Domain\Contracts;

use Billing\Application\DTOs\CreateSubscriptionDTO;
use Billing\Application\DTOs\SubscriptionResult;

interface PaymentGatewayInterface
{
    public function subscribe(string $customerId, CreateSubscriptionDTO $dto): SubscriptionResult;

    public function updateSubscription(string $gatewaySubscriptionId, string $newPlanId): SubscriptionResult;

    public function refund(string $paymentIntentId, int $amount): void;

    public function retryPayment(string $invoiceId): SubscriptionResult;
}
