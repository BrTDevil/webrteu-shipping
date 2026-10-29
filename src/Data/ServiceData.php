<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

final readonly class ServiceData
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $code,
        public string $name,
        public bool $pickupPoint = false,
        public array $raw = [],
    ) {}
}
