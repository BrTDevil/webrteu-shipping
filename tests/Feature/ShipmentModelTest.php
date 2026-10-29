<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Data\AwbData;
use WeBRTeu\Shipping\Data\ParcelData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Data\TrackingEventData;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Events\ShipmentStatusChanged;
use WeBRTeu\Shipping\Models\Shipment;
use WeBRTeu\Shipping\Models\ShipmentEvent;

function shipmentData(): ShipmentData
{
    return new ShipmentData(
        recipient: new AddressData('Ion Popescu', '0721000000', 'Brașov', 'Brașov', 'Str. Lungă', '10'),
        parcels: [new ParcelData(1.5)],
        reference: 'CMD-100',
        codAmount: 14170,
    );
}

function deliveredTracking(): TrackingData
{
    return TrackingData::fromEvents('AWB100', [
        new TrackingEventData(ShipmentStatus::PickedUp, '2', 'Preluat', CarbonImmutable::parse('2026-01-01 10:00'), 'Brașov'),
        new TrackingEventData(ShipmentStatus::Delivered, '9', 'Livrat', CarbonImmutable::parse('2026-01-02 12:00'), 'Brașov', ['x' => 1]),
    ]);
}

it('records a shipment from an AWB', function (): void {
    $order = new class extends Model
    {
        protected $table = 'orders';
    };
    $order->id = 7;

    $shipment = Shipment::recordFrom(new AwbData('AWB100', 'fake', 1999, ['1'], ['ok' => true]), shipmentData(), $order);

    expect($shipment->fresh())
        ->carrier->toBe('fake')
        ->awb->toBe('AWB100')
        ->reference->toBe('CMD-100')
        ->status->toBe(ShipmentStatus::Created)
        ->status_label->toBe('AWB generat')
        ->cost->toBe(1999)
        ->cod_amount->toBe(14170)
        ->raw->toBe(['ok' => true])
        ->shippable_type->toBe($order::class)
        ->shippable_id->toBe(7);
});

it('records a shipment without a shippable model', function (): void {
    $shipment = Shipment::recordFrom(new AwbData('AWB100', 'fake'), shipmentData());

    expect($shipment->shippable_type)->toBeNull()
        ->and($shipment->shippable)->toBeNull()
        ->and($shipment->getTable())->toBe('shipments');
});

it('applies tracking, stores events and emits the change once', function (): void {
    Event::fake([ShipmentStatusChanged::class]);
    $shipment = Shipment::recordFrom(new AwbData('AWB100', 'fake'), shipmentData());

    expect($shipment->applyTracking(deliveredTracking()))->toBeTrue()
        ->and($shipment->applyTracking(deliveredTracking()))->toBeFalse();

    $shipment->refresh();

    expect($shipment->status)->toBe(ShipmentStatus::Delivered)
        ->and($shipment->delivered_at?->toDateTimeString())->toBe('2026-01-02 12:00:00')
        ->and($shipment->last_tracked_at)->not->toBeNull()
        ->and($shipment->events()->count())->toBe(2);

    /** @var ShipmentEvent $event */
    $event = $shipment->events()->where('code', '9')->firstOrFail();

    expect($event->status)->toBe(ShipmentStatus::Delivered)
        ->and($event->raw)->toBe(['x' => 1])
        ->and($event->occurred_at?->toDateTimeString())->toBe('2026-01-02 12:00:00')
        ->and($event->shipment->is($shipment))->toBeTrue()
        ->and($event->getTable())->toBe('shipment_events');

    Event::assertDispatchedTimes(ShipmentStatusChanged::class, 1);
    Event::assertDispatched(fn (ShipmentStatusChanged $changed): bool => $changed->from === ShipmentStatus::Created
        && $changed->to === ShipmentStatus::Delivered
        && $changed->shipment->is($shipment));
});

it('does not duplicate events without a date', function (): void {
    $shipment = Shipment::recordFrom(new AwbData('AWB100', 'fake'), shipmentData());
    $tracking = TrackingData::fromEvents('AWB100', [new TrackingEventData(ShipmentStatus::InTransit, '3', 'Tranzit')]);

    $shipment->applyTracking($tracking);
    $shipment->applyTracking($tracking);

    expect($shipment->events()->count())->toBe(1);
});

it('scopes pending shipments to non-final and recent ones', function (): void {
    $pending = Shipment::recordFrom(new AwbData('A1', 'fake'), shipmentData());
    $delivered = Shipment::recordFrom(new AwbData('A2', 'fake'), shipmentData());
    $delivered->forceFill(['status' => ShipmentStatus::Delivered])->save();
    $old = Shipment::recordFrom(new AwbData('A3', 'fake'), shipmentData());
    $old->forceFill(['created_at' => now()->subDays(46)])->save();

    expect(Shipment::query()->pending()->pluck('awb')->all())->toBe([$pending->awb]);
});
