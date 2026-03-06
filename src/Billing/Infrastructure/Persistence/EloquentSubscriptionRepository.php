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

    public function findByGatewayId(string $gatewayId): ?Subscription
    {
        $model = SubscriptionModel::where('gateway_id', $gatewayId)->first();

        if (! $model) {
            return null;
        }

        return Subscription::create(
            customerId: $model->customer_id,
            planId: $model->plan_id,
            status: \Billing\Domain\ValueObjects\SubscriptionStatus::from($model->status),
            gatewayId: $model->gateway_id,
            id: (string) $model->id
        );
    }
}
