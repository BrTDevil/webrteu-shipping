<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use WeBRTeu\Shipping\ShippingServiceProvider;

it('merges the default config', function (): void {
    expect(config('shipping.sync.batch_size'))->toBe(50)
        ->and(config('shipping.tables.shipments'))->toBe('shipments');
});

it('publishes the config and the migration', function (): void {
    expect(ServiceProvider::pathsToPublish(ShippingServiceProvider::class, 'shipping-config'))->toHaveCount(1)
        ->and(array_values(ServiceProvider::pathsToPublish(ShippingServiceProvider::class, 'shipping-migrations'))[0])
        ->toEndWith('_create_shipments_tables.php');
});
