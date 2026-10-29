<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Shipping\Models\Shipment;
use WeBRTeu\Shipping\ShippingManager;

/**
 * Sincronizează un singur AWB, pentru declanșare manuală din aplicație:
 * dispatch(new SyncShipmentStatus($shipment)).
 */
class SyncShipmentStatus implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Shipment $shipment)
    {
        $queue = Config::get('shipping.sync.queue');

        if (is_string($queue) && $queue !== '') {
            $this->onQueue($queue);
        }
    }

    public function handle(ShippingManager $shipping): void
    {
        $tracking = $shipping->carrier($this->shipment->carrier)->track($this->shipment->awb);

        $this->shipment->applyTracking($tracking);
    }
}
