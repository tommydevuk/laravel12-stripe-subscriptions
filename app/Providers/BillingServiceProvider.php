<?php

declare(strict_types=1);

namespace App\Providers;

use Billing\Domain\Contracts\PaymentGatewayInterface;
use Billing\Domain\Contracts\SubscriptionRepositoryInterface;
use Billing\Infrastructure\Persistence\EloquentSubscriptionRepository;
use Billing\Infrastructure\Persistence\PriceModel;
use Billing\Infrastructure\Persistence\ProductModel;
use Billing\Infrastructure\Stripe\Observers\PriceObserver;
use Billing\Infrastructure\Stripe\Observers\ProductObserver;
use Billing\Infrastructure\Stripe\StripePaymentGateway;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(StripeClient::class, function () {
            return new StripeClient(config('services.stripe.secret'));
        });

        $this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(base_path('src/Billing/Infrastructure/Persistence/Migrations'));

        ProductModel::observe(ProductObserver::class);
        PriceModel::observe(PriceObserver::class);
    }
}
