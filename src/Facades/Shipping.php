<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Facades;

use Illuminate\Support\Facades\Facade;
use WeBRTeu\Shipping\ShippingManager;

/**
 * @method static \WeBRTeu\Shipping\Contracts\Carrier carrier(?string $name = null)
 * @method static ShippingManager extend(string $name, \Closure $creator)
 * @method static bool has(string $name)
 * @method static list<string> available()
 * @method static string getDefaultCarrier()
 *
 * @see ShippingManager
 */
class Shipping extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ShippingManager::class;
    }
}
