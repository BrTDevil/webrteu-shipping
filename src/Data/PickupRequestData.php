<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

use Carbon\CarbonImmutable;

final readonly class PickupRequestData
{
    /**
     * @param  string  $fromHour  „HH:MM”
     * @param  string  $toHour  „HH:MM”
     * @param  AddressData|null  $address  null = punctul de ridicare din configurarea curierului
     */
    public function __construct(
        public CarbonImmutable $date,
        public string $fromHour,
        public string $toHour,
        public int $parcels,
        public float $totalWeightKg,
        public ?AddressData $address = null,
        public ?string $observations = null,
    ) {}
}
