<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

use WeBRTeu\Shipping\Enums\PickupPointType;

final readonly class PickupPointData
{
    /**
     * @param  array<array-key, mixed>|null  $schedule
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $carrier,
        public PickupPointType $type,
        public string $name,
        public string $county,
        public string $city,
        public string $address,
        public ?float $lat = null,
        public ?float $lng = null,
        public ?string $postalCode = null,
        public bool $supportsCod = false,
        public ?array $schedule = null,
        public array $raw = [],
    ) {}

    /**
     * @return array{id: string, carrier: string, type: string, name: string, county: string, city: string, address: string, lat: float|null, lng: float|null, postal_code: string|null, supports_cod: bool, schedule: array<array-key, mixed>|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'carrier' => $this->carrier,
            'type' => $this->type->value,
            'name' => $this->name,
            'county' => $this->county,
            'city' => $this->city,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'postal_code' => $this->postalCode,
            'supports_cod' => $this->supportsCod,
            'schedule' => $this->schedule,
        ];
    }
}
