<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Stripe;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class StripeWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return in_array($request->json('type'), [
            'customer.subscription.created',
            'customer.subscription.updated',
            'invoice.payment_failed',
            'product.created',
            'product.updated',
            'price.created',
            'price.updated',
        ]);
    }
}
