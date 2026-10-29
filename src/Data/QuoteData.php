<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

final readonly class QuoteData
{
    /**
     * @param  int  $amount  în bani
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $carrier,
        public int $amount,
        public string $currency = 'RON',
        public ?string $serviceCode = null,
        public array $raw = [],
    ) {}
}
