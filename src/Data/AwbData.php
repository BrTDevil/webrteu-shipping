<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

final readonly class AwbData
{
    /**
     * @param  int|null  $cost  în bani
     * @param  list<string>  $parcelNumbers
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $awb,
        public string $carrier,
        public ?int $cost = null,
        public array $parcelNumbers = [],
        public array $raw = [],
    ) {}
}
