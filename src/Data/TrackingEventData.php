<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

use Carbon\CarbonImmutable;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

final readonly class TrackingEventData
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ShipmentStatus $status,
        public string $code,
        public string $label,
        public ?CarbonImmutable $occurredAt = null,
        public ?string $location = null,
        public array $raw = [],
    ) {}
}
