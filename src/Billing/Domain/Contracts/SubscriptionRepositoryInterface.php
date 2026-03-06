<?php

declare(strict_types=1);

namespace Billing\Domain\Contracts;

use Billing\Domain\Entities\Subscription;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void;
}
