<?php

declare(strict_types=1);

namespace Billing\Infrastructure\Laravel\Console\Commands;

use Billing\Infrastructure\Persistence\CustomerModel;
use Billing\Infrastructure\Persistence\PriceModel;
use Billing\Infrastructure\Persistence\ProductModel;
use Billing\Infrastructure\Persistence\SubscriptionModel;
use Illuminate\Console\Command;
use Stripe\StripeClient;

final class StripeSyncCommand extends Command
{
    protected $signature = 'billing:stripe-sync';

    protected $description = 'Import all Stripe persistent resources (products, prices, customers, subscriptions) into the local database';

    public function handle(StripeClient $stripe): void
    {
        $this->info('Starting full Stripe-to-Laravel sync...');

        $this->syncProducts($stripe);
        $this->syncPrices($stripe);
        $this->syncCustomers($stripe);
        $this->syncSubscriptions($stripe);

        $this->info('Stripe sync complete.');
    }

    private function syncProducts(StripeClient $stripe): void
    {
        $this->info('Syncing products...');

        foreach ($stripe->products->all(['limit' => 100])->autoPagingIterator() as $prod) {
            ProductModel::withoutStripeSync(function () use ($prod): void {
                ProductModel::updateOrCreate(
                    ['stripe_id' => $prod->id],
                    [
                        'name' => $prod->name,
                        'description' => $prod->description ?? null,
                        'active' => (bool) $prod->active,
                        'metadata' => $prod->metadata ?? [],
                    ]
                );
            });
        }

        $this->info('Products synced.');
    }

    private function syncPrices(StripeClient $stripe): void
    {
        $this->info('Syncing prices...');

        foreach ($stripe->prices->all(['limit' => 100])->autoPagingIterator() as $pr) {
            PriceModel::withoutStripeSync(function () use ($pr): void {
                $product = ProductModel::where('stripe_id', $pr->product)->first();

                if (! $product) {
                    $this->warn("Price {$pr->id} references missing product {$pr->product}, skipping");
                    return;
                }

                PriceModel::updateOrCreate(
                    ['stripe_id' => $pr->id],
                    [
                        'product_id' => $product->id,
                        'amount' => $pr->unit_amount,
                        'currency' => $pr->currency,
                        'type' => $pr->type,
                        'interval' => $pr->recurring->interval ?? null,
                        'active' => (bool) $pr->active,
                        'metadata' => $pr->metadata ?? [],
                    ]
                );
            });
        }

        $this->info('Prices synced.');
    }

    private function syncCustomers(StripeClient $stripe): void
    {
        $this->info('Syncing customers...');

        foreach ($stripe->customers->all(['limit' => 100])->autoPagingIterator() as $cust) {
            CustomerModel::withoutStripeSync(function () use ($cust): void {
                CustomerModel::updateOrCreate(
                    ['stripe_id' => $cust->id],
                    [
                        'name' => $cust->name ?? null,
                        'email' => $cust->email ?? null,
                        'metadata' => $cust->metadata ?? [],
                    ]
                );
            });
        }

        $this->info('Customers synced.');
    }

    private function syncSubscriptions(StripeClient $stripe): void
    {
        $this->info('Syncing subscriptions...');

        foreach ($stripe->subscriptions->all(['limit' => 100])->autoPagingIterator() as $sub) {
            SubscriptionModel::updateOrCreate(
                ['gateway_id' => $sub->id],
                [
                    'customer_id' => $sub->customer,
                    'plan_id' => $sub->items->data[0]->plan->id,
                    'status' => $sub->status,
                ]
            );
        }

        $this->info('Subscriptions synced.');
    }
}
