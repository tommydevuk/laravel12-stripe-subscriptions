<?php

declare(strict_types=1);

namespace Billing\Application\Actions;

use Billing\Application\DTOs\CreateSubscriptionDTO;
use Billing\Domain\Contracts\PaymentGatewayInterface;
use Billing\Domain\Contracts\SubscriptionRepositoryInterface;
use Billing\Domain\Entities\Subscription;
use Billing\Domain\Events\SubscriptionCreated;
use Illuminate\Support\Facades\Event;

class CreateSubscription
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private SubscriptionRepositoryInterface $repository,
    ) {}

    public function execute(CreateSubscriptionDTO $dto): Subscription
    {
        // 1. Call gateway (outside any DB transaction intentionally)
        $result = $this->gateway->subscribe($dto->customerId, $dto);

        // 2. Build domain entity from normalised result
        $subscription = Subscription::create(
            customerId: $dto->customerId,
            planId: $dto->planId,
            status: $result->status,
            gatewayId: $result->gatewaySubscriptionId,
        );

        // 3. Persist
        $this->repository->save($subscription);

        // 4. Dispatch domain event
        Event::dispatch(new SubscriptionCreated($subscription));

        return $subscription;
    }
}
