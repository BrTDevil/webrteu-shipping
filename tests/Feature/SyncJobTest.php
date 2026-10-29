<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Facades\Shipping;
use WeBRTeu\Shipping\Jobs\SyncShipmentStatus;
use WeBRTeu\Shipping\Models\Shipment;
use WeBRTeu\Shipping\Tests\Fakes\FakeCarrier;

it('syncs one shipment', function (): void {
    $carrier = (new FakeCarrier)->willTrack('A1', ShipmentStatus::OutForDelivery, 'În livrare');
    Shipping::extend('fake', fn (): FakeCarrier => $carrier);
    $shipment = Shipment::query()->create(['carrier' => 'fake', 'awb' => 'A1', 'status' => ShipmentStatus::Created]);

    Bus::dispatchSync(new SyncShipmentStatus($shipment));

    expect($shipment->fresh()?->status)->toBe(ShipmentStatus::OutForDelivery)
        ->and($shipment->fresh()?->status_label)->toBe('În livrare');
});

it('uses the configured queue', function (?string $queue, ?string $expected): void {
    Config::set('shipping.sync.queue', $queue);
    $shipment = Shipment::query()->create(['carrier' => 'fake', 'awb' => 'A1', 'status' => ShipmentStatus::Created]);

    expect((new SyncShipmentStatus($shipment))->queue)->toBe($expected);
})->with([
    ['tracking', 'tracking'],
    [null, null],
    ['', null],
]);
