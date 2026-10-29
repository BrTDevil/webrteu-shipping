<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;

final readonly class ParcelData
{
    public function __construct(
        public float $weightKg,
        public ?int $lengthCm = null,
        public ?int $widthCm = null,
        public ?int $heightCm = null,
    ) {
        if ($weightKg <= 0) {
            throw new ShipmentValidationException('Greutatea coletului trebuie să fie mai mare decât 0 kg.');
        }
    }
}
