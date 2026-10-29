<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Tests\Fakes;

use Illuminate\Support\Collection;
use Throwable;
use WeBRTeu\Shipping\Contracts\HasBulkTracking;
use WeBRTeu\Shipping\Contracts\HasCourierOrder;
use WeBRTeu\Shipping\Contracts\HasPickupPoints;
use WeBRTeu\Shipping\Data\PickupPointData;
use WeBRTeu\Shipping\Data\PickupRequestData;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Enums\PickupPointType;

/**
 * Curier fals care implementează toate interfețele opționale.
 */
class FakeBulkCarrier extends FakeCarrier implements HasBulkTracking, HasCourierOrder, HasPickupPoints
{
    /**
     * @var list<list<string>>
     */
    public array $bulkCalls = [];

    public ?Throwable $bulkFailure = null;

    public function trackMany(array $awbs): Collection
    {
        $this->bulkCalls[] = $awbs;

        if ($this->bulkFailure instanceof Throwable) {
            throw $this->bulkFailure;
        }

        /** @var Collection<string, TrackingData> $results */
        $results = collect();

        foreach ($awbs as $awb) {
            if (isset($this->tracking[$awb])) {
                $results->put($awb, $this->result($awb));
            }
        }

        return $results;
    }

    public function pickupPoints(?string $county = null, ?string $city = null): Collection
    {
        return collect([
            new PickupPointData('1', $this->name(), PickupPointType::Locker, 'Locker 1', 'Brașov', 'Brașov', 'Str. Lungă 1'),
        ]);
    }

    public function orderPickup(PickupRequestData $request): string
    {
        return 'PICKUP-1';
    }
}
