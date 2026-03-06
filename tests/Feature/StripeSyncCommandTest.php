<?php

declare(strict_types=1);

use Billing\Infrastructure\Persistence\ProductModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\StripeClient;

uses(RefreshDatabase::class)
    ->beforeEach(function () {
        // run in-memory sqlite to avoid leftover tables from previous runs
        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite.database' => ':memory:']);
    });

it('imports stripe resources without failing', function () {
    // build a fake Stripe product
    $product = (object)[
        'id' => 'prod_test',
        'name' => 'Test product',
        'description' => null,
        'active' => true,
        'metadata' => [],
    ];

    $productCollection = new class([$product]) {
        private array $items;

        public function __construct(array $items)
        {
            $this->items = $items;
        }

        public function autoPagingIterator()
        {
            return new ArrayIterator($this->items);
        }
    };

    $emptyCollection = new class([]) {
        public function __construct(array $_) {}
        public function autoPagingIterator()
        {
            return new ArrayIterator([]);
        }
    };

    $fakeStripe = \Mockery::mock(StripeClient::class);
    $fakeStripe->products = \Mockery::mock();
    $fakeStripe->products->shouldReceive('all')->andReturn($productCollection);

    $fakeStripe->prices = \Mockery::mock();
    $fakeStripe->prices->shouldReceive('all')->andReturn($emptyCollection);

    $fakeStripe->customers = \Mockery::mock();
    $fakeStripe->customers->shouldReceive('all')->andReturn($emptyCollection);

    $fakeStripe->subscriptions = \Mockery::mock();
    $fakeStripe->subscriptions->shouldReceive('all')->andReturn($emptyCollection);

    $this->app->instance(StripeClient::class, $fakeStripe);

    $this->artisan('billing:stripe-sync')
        ->expectsOutput('Starting full Stripe-to-Laravel sync...')
        ->expectsOutput('Products synced.')
        ->expectsOutput('Prices synced.')
        ->expectsOutput('Customers synced.')
        ->expectsOutput('Subscriptions synced.')
        ->expectsOutput('Stripe sync complete.')
        ->assertExitCode(0);

    expect(ProductModel::where('stripe_id', 'prod_test')->exists())->toBeTrue();
});
