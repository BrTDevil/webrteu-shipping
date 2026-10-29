<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Events;

use Illuminate\Queue\SerializesModels;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Models\Shipment;

final class ShipmentStatusChanged
{
    use SerializesModels;

    public function __construct(
        public Shipment $shipment,
        public ShipmentStatus $from,
        public ShipmentStatus $to,
    ) {}
}
