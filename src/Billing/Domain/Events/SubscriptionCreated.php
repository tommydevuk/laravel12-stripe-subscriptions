<?php

declare(strict_types=1);

namespace Billing\Domain\Events;

use Billing\Domain\Entities\Subscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Subscription $subscription) {}
}
