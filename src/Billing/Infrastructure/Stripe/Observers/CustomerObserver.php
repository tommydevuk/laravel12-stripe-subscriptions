<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe\Observers;

use Billing\Infrastructure\Persistence\CustomerModel;
use Billing\Infrastructure\Stripe\StripeSyncable;
use Stripe\StripeClient;
use Throwable;

final class CustomerObserver
{
    use StripeSyncable;

    public function __construct(
        private readonly StripeClient $stripe
    ) {}

    public function created(CustomerModel $customer): void
    {
        if (StripeSyncable::\$disabled) {
            return;
        }

        try {
            $stripeCustomer = $this->stripe->customers->create([
                'name' => $customer->name,
                'email' => $customer->email,
                'metadata' => $customer->metadata,
            ]);

            CustomerModel::withoutStripeSync(fn () => $customer->update(['stripe_id' => $stripeCustomer->id]));
        } catch (Throwable) {
            // log or handle
        }
    }

    public function updated(CustomerModel $customer): void
    {
        if (StripeSyncable::\$disabled || empty($customer->stripe_id)) {
            return;
        }

        try {
            $this->stripe->customers->update($customer->stripe_id, [
                'name' => $customer->name,
                'email' => $customer->email,
                'metadata' => $customer->metadata,
            ]);
        } catch (Throwable) {
            // log or handle
        }
    }
}