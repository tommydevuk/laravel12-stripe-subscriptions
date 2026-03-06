<?php

declare(strict_types=1);

namespace App\Console\Commands\Subscriptions;

use Billing\Application\Actions\CreateSubscription;
use Billing\Application\DTOs\CreateSubscriptionDTO;
use Illuminate\Console\Command;

class CreateSubscriptionCommand extends Command
{
    protected $signature = 'billing:subscribe
                            {customerId}
                            {planId}
                            {paymentMethodId}';

    protected $description = 'Create a new subscription via CLI';

    public function handle(CreateSubscription $action): void
    {
        $subscription = $action->execute(new CreateSubscriptionDTO(
            customerId: $this->argument('customerId'),
            planId: $this->argument('planId'),
            paymentMethodId: $this->argument('paymentMethodId'),
        ));

        $this->info("Subscription created: {$subscription->gatewayId}");
    }
}
