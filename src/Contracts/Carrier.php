<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Contracts;

use Illuminate\Support\Collection;
use WeBRTeu\Shipping\Data\AwbData;
use WeBRTeu\Shipping\Data\QuoteData;
use WeBRTeu\Shipping\Data\ServiceData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Enums\LabelFormat;

interface Carrier
{
    /**
     * Identificatorul curierului: „sameday”, „fancourier”...
     */
    public function name(): string;

    /**
     * @return Collection<int, ServiceData>
     */
    public function services(): Collection;

    public function quote(ShipmentData $shipment): QuoteData;

    /**
     * Fără retry automat: un timeout aruncă ShipmentStateUnknownException.
     */
    public function createShipment(ShipmentData $shipment): AwbData;

    /**
     * @return string conținutul binar al etichetei (PDF sau ZPL)
     */
    public function label(string $awb, LabelFormat $format = LabelFormat::A6Pdf): string;

    public function track(string $awb): TrackingData;

    public function cancel(string $awb): bool;
}
