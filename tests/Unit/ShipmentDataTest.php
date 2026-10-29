<?php

declare(strict_types=1);

use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Data\ParcelData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;

function recipient(): AddressData
{
    return new AddressData(
        name: 'Ion Popescu',
        phone: '0721000000',
        county: 'Brașov',
        city: 'Brașov',
        street: 'Str. Lungă',
        number: '10',
    );
}

it('requires at least one parcel or envelope', function (): void {
    new ShipmentData(recipient(), [], 'CMD-1');
})->throws(ShipmentValidationException::class, 'cel puțin un colet sau un plic');

it('accepts envelopes without parcels', function (): void {
    $shipment = new ShipmentData(recipient(), [], 'CMD-1', envelopes: 1);

    expect($shipment->parcelCount())->toBe(0)
        ->and($shipment->totalWeightKg())->toBe(0.0);
});

it('rejects a negative envelope count', function (): void {
    new ShipmentData(recipient(), [new ParcelData(1)], 'CMD-1', envelopes: -1);
})->throws(ShipmentValidationException::class);

it('rejects negative amounts', function (int $cod, int $declared): void {
    new ShipmentData(recipient(), [new ParcelData(1)], 'CMD-1', codAmount: $cod, declaredValue: $declared);
})->with([[-1, 0], [0, -1]])->throws(ShipmentValidationException::class);

it('rejects a parcel weight of zero or less', function (float $weight): void {
    new ParcelData($weight);
})->with([0.0, -1.5])->throws(ShipmentValidationException::class, 'mai mare decât 0');

it('sums the parcel weights', function (): void {
    $shipment = new ShipmentData(recipient(), [new ParcelData(1.2), new ParcelData(0.3, 10, 20, 30)], 'CMD-1');

    expect($shipment->totalWeightKg())->toBe(1.5)
        ->and($shipment->parcelCount())->toBe(2)
        ->and($shipment->hasCashOnDelivery())->toBeFalse()
        ->and($shipment->isPickupPointDelivery())->toBeFalse();
});

it('knows about cash on delivery and pickup points', function (): void {
    $shipment = new ShipmentData(recipient(), [new ParcelData(1)], 'CMD-1', codAmount: 14170, pickupPointId: '123');

    expect($shipment->hasCashOnDelivery())->toBeTrue()
        ->and($shipment->isPickupPointDelivery())->toBeTrue()
        ->and((new ShipmentData(recipient(), [new ParcelData(1)], 'CMD-1', pickupPointId: ''))->isPickupPointDelivery())->toBeFalse();
});

it('formats the address on one line and the contact', function (): void {
    $address = new AddressData(
        name: 'Ion Popescu',
        phone: '0721000000',
        county: 'Brașov',
        city: 'Brașov',
        street: 'Str. Lungă',
        number: '10',
        contactPerson: 'Maria Ionescu',
        block: 'A1',
        staircase: '2',
        floor: '3',
        apartment: '12',
        postalCode: '500001',
        company: 'Exemplu SRL',
    );

    expect($address->line())->toBe('Str. Lungă nr. 10, bl. A1, sc. 2, et. 3, ap. 12, Brașov, jud. Brașov, 500001')
        ->and($address->contact())->toBe('Maria Ionescu (Exemplu SRL), 0721000000')
        ->and(recipient()->line())->toBe('Str. Lungă nr. 10, Brașov, jud. Brașov')
        ->and(recipient()->contact())->toBe('Ion Popescu, 0721000000');
});
