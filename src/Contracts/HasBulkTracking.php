<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Contracts;

use Illuminate\Support\Collection;
use WeBRTeu\Shipping\Data\TrackingData;

interface HasBulkTracking
{
    /**
     * @param  list<string>  $awbs
     * @return Collection<string, TrackingData> indexat după AWB
     */
    public function trackMany(array $awbs): Collection;
}
