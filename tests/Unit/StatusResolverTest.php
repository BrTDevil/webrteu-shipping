<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Support\StatusResolver;

it('prefers the application config override', function (): void {
    Config::set('shipping.status_map.fancourier.S2', 'delivered');

    expect(StatusResolver::resolve('fancourier', 'S2', 'Altceva', ['S2' => ShipmentStatus::InTransit]))
        ->toBe(ShipmentStatus::Delivered);
});

it('accepts enum instances in the config override', function (): void {
    Config::set('shipping.status_map.fancourier', ['S2' => ShipmentStatus::Returned]);

    expect(StatusResolver::resolve('fancourier', 'S2', ''))->toBe(ShipmentStatus::Returned);
});

it('ignores invalid config overrides and uses the built-in map', function (): void {
    Config::set('shipping.status_map.fancourier.S2', 'not-a-status');

    expect(StatusResolver::resolve('fancourier', 'S2', '', ['S2' => ShipmentStatus::InTransit]))
        ->toBe(ShipmentStatus::InTransit);
});

it('falls back to keywords in the label', function (string $label, ShipmentStatus $expected): void {
    expect(StatusResolver::resolve('fake', 'X', $label))->toBe($expected);
})->with([
    ['Colet livrat destinatarului', ShipmentStatus::Delivered],
    ['Retur către expeditor', ShipmentStatus::Returning],
    ['În tranzit spre depozit', ShipmentStatus::InTransit],
    ['Ridicat de la expeditor', ShipmentStatus::PickedUp],
    ['Preluat în depozit', ShipmentStatus::PickedUp],
    ['În livrare', ShipmentStatus::OutForDelivery],
    ['Coletul este la curier', ShipmentStatus::OutForDelivery],
    ['Depus în easybox', ShipmentStatus::AtPickupPoint],
    ['Locker plin', ShipmentStatus::AtPickupPoint],
    ['Ajuns în punctul de ridicare', ShipmentStatus::AtPickupPoint],
    ['AWB anulat', ShipmentStatus::Cancelled],
    ['Expediție înregistrată', ShipmentStatus::Created],
]);

it('returns unknown and logs a warning when nothing matches', function (): void {
    Log::spy();

    expect(StatusResolver::resolve('fake', 'Z9', 'Cod misterios'))->toBe(ShipmentStatus::Unknown);

    Log::shouldHaveReceived('warning')->once()->with('shipping.unknown_status', [
        'carrier' => 'fake',
        'code' => 'Z9',
        'label' => 'Cod misterios',
    ]);
});
