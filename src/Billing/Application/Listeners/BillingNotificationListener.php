<?php

declare(strict_types=1);

namespace Billing\Application\Listeners;

use Billing\Domain\Events\SubscriptionCreated;
use Billing\Domain\Events\SubscriptionPriceChanged;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class BillingNotificationListener
{
    public function handleSubscriptionCreated(SubscriptionCreated $event): void
    {
        $user = User::where('stripe_customer_id', $event->subscription->customerId)->first();

        if ($user) {
            // Confirmation logic (e.g., Mail, Database notification)
            Log::info("User {$user->id} confirmed for subscription {$event->subscription->gatewayId}.");
            // In real app: $user->notify(new SubscriptionConfirmed($event->subscription));
        }
    }

    public function handlePriceChanged(SubscriptionPriceChanged $event): void
    {
        // Extract customer from subscription
        // In this simple implementation, we assume we can find the user via customer_id
        // In a real app, the event might carry the userId directly or we look it up.
        
        Log::info("Subscription {$event->gatewaySubscriptionId} price changed from {$event->oldAmount} to {$event->newAmount} effective at {$event->effectiveAt}.");
    }
}
