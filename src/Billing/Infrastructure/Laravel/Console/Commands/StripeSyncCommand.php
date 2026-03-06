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

        $this->runSection('Syncing products', fn() => $this->syncProducts($stripe));
        $this->runSection('Syncing prices', fn() => $this->syncPrices($stripe));
        $this->runSection('Syncing customers', fn() => $this->syncCustomers($stripe));
        $this->runSection('Syncing subscriptions', fn() => $this->syncSubscriptions($stripe));

        $this->info('Stripe sync complete.');
    }

    /**
     * Execute a callable and catch any table-not-found errors so the command can
     * continue gracefully when run before migrations.
     */
    private function runSection(string $description, callable $callback): void
    {
        $this->info("{$description}...");

        try {
            $callback();
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Base table or view not found')) {
                $this->warn("Skipping {$description}: {$e->getMessage()}");
                return;
            }

            throw $e;
        }

        $this->info(ucfirst(str_replace('Syncing ', '', $description)).' synced.');
    }

    private function syncProducts(StripeClient $stripe): void
    {
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

    }

    private function syncPrices(StripeClient $stripe): void
    {

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

    }

    private function syncCustomers(StripeClient $stripe): void
    {

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

    }

    private function syncSubscriptions(StripeClient $stripe): void
    {

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

    }
}
