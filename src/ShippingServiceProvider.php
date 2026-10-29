<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Shipping\Commands\SyncShipmentsCommand;

class ShippingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shipping.php', 'shipping');

        $this->app->singleton(ShippingManager::class, fn (Container $app): ShippingManager => new ShippingManager($app));
        $this->app->alias(ShippingManager::class, 'shipping');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/shipping.php' => $this->app->configPath('shipping.php'),
        ], 'shipping-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_shipments_tables.php.stub' => $this->app->databasePath('migrations/'.date('Y_m_d_His').'_create_shipments_tables.php'),
        ], 'shipping-migrations');

        $this->commands([SyncShipmentsCommand::class]);
    }
}
