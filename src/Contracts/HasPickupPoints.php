<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Contracts;

use Illuminate\Support\Collection;
use WeBRTeu\Shipping\Data\PickupPointData;

/**
 * Lockere și puncte de ridicare: easybox, FANbox, Ship & Go, GLS Locker, DPD Box.
 */
interface HasPickupPoints
{
    /**
     * @return Collection<int, PickupPointData>
     */
    public function pickupPoints(?string $county = null, ?string $city = null): Collection;
}
