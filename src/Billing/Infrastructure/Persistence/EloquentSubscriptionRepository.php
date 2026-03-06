<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Persistence;

use Billing\Domain\Contracts\SubscriptionRepositoryInterface;
use Billing\Domain\Entities\Subscription;

class EloquentSubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void
    {
        SubscriptionModel::updateOrCreate(
            ['gateway_id' => $subscription->gatewayId],
            [
                'customer_id' => $subscription->customerId,
                'plan_id' => $subscription->planId,
                'status' => $subscription->status->value,
            ]
        );
    }
}
