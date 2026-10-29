<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Data\TrackingEventData;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

it('sorts events chronologically and takes the status of the last one', function (): void {
    $delivered = new TrackingEventData(ShipmentStatus::Delivered, '9', 'Livrat', CarbonImmutable::parse('2026-01-03 10:00'));
    $created = new TrackingEventData(ShipmentStatus::Created, '1', 'Înregistrat', CarbonImmutable::parse('2026-01-01 09:00'));
    $transit = new TrackingEventData(ShipmentStatus::InTransit, '3', 'În tranzit', CarbonImmutable::parse('2026-01-02 12:00'));

    $tracking = TrackingData::fromEvents('AWB1', [$delivered, $created, $transit], ['source' => 'test']);

    expect($tracking->events)->toBe([$created, $transit, $delivered])
        ->and($tracking->status)->toBe(ShipmentStatus::Delivered)
        ->and($tracking->statusLabel)->toBe('Livrat')
        ->and($tracking->deliveredAt?->toDateTimeString())->toBe('2026-01-03 10:00:00')
        ->and($tracking->lastEvent())->toBe($delivered)
        ->and($tracking->raw)->toBe(['source' => 'test']);
});

it('keeps the delivered date even when a later event follows', function (): void {
    $tracking = TrackingData::fromEvents('AWB1', [
        new TrackingEventData(ShipmentStatus::Delivered, '9', 'Livrat', CarbonImmutable::parse('2026-01-03 10:00')),
        new TrackingEventData(ShipmentStatus::Returning, '12', 'Retur', CarbonImmutable::parse('2026-01-04 10:00')),
    ]);

    expect($tracking->status)->toBe(ShipmentStatus::Returning)
        ->and($tracking->deliveredAt?->toDateTimeString())->toBe('2026-01-03 10:00:00');
});

it('puts events without a date first', function (): void {
    $undated = new TrackingEventData(ShipmentStatus::Created, '1', 'Înregistrat');
    $dated = new TrackingEventData(ShipmentStatus::InTransit, '3', 'În tranzit', CarbonImmutable::parse('2026-01-02'));

    expect(TrackingData::fromEvents('AWB1', [$dated, $undated])->events)->toBe([$undated, $dated]);
});

it('returns an unknown status when there are no events', function (): void {
    $tracking = TrackingData::fromEvents('AWB1', []);

    expect($tracking->status)->toBe(ShipmentStatus::Unknown)
        ->and($tracking->statusLabel)->toBe('Status necunoscut')
        ->and($tracking->deliveredAt)->toBeNull()
        ->and($tracking->lastEvent())->toBeNull();
});
