<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | These settings configure the Stripe payment gateway integration for the
    | billing module. Ensure STRIPE_SECRET_KEY and STRIPE_WEBHOOK_SECRET are
    | set in your environment.
    |
    */

    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Control which billing features are enabled in your application.
    |
    */

    'features' => [
        'subscriptions' => true,
        'refunds' => true,
        'payment_retries' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Endpoint path and signing options for Stripe webhooks.
    |
    */

    'webhooks' => [
        'path' => env('STRIPE_WEBHOOK_PATH', 'api/stripe-webhooks'),
        'verify_signature' => true,
    ],
];
