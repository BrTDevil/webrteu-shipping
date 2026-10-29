<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Shipping\Data\AddressData;
use WeBRTeu\Shipping\Data\ParcelData;
use WeBRTeu\Shipping\Data\PickupRequestData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Exceptions\ShippingException;
use WeBRTeu\Shipping\Facades\Shipping;
use WeBRTeu\Shipping\ShippingManager;
use WeBRTeu\Shipping\Tests\Fakes\FakeBulkCarrier;
use WeBRTeu\Shipping\Tests\Fakes\FakeCarrier;

it('registers carriers with extend and resolves them once', function (): void {
    $created = 0;

    Shipping::extend('fake', function (Container $app) use (&$created): FakeCarrier {
        $created++;

        return new FakeCarrier;
    });

    expect(Shipping::carrier('fake'))->toBeInstanceOf(FakeCarrier::class)
        ->and(Shipping::carrier('fake'))->toBe(Shipping::carrier('fake'))
        ->and($created)->toBe(1)
        ->and(Shipping::has('fake'))->toBeTrue()
        ->and(Shipping::has('gls'))->toBeFalse();
});

it('uses the default carrier from config', function (): void {
    Config::set('shipping.default', 'bulk');
    Shipping::extend('bulk', fn (): FakeBulkCarrier => new FakeBulkCarrier('bulk'));

    expect(Shipping::carrier()->name())->toBe('bulk')
        ->and(Shipping::getDefaultCarrier())->toBe('bulk');
});

it('lists the available carriers', function (): void {
    Shipping::extend('one', fn (): FakeCarrier => new FakeCarrier('one'))
        ->extend('two', fn (): FakeCarrier => new FakeCarrier('two'));

    expect(Shipping::available())->toBe(['one', 'two']);
});

it('re-resolves a carrier after it is extended again', function (): void {
    Shipping::extend('fake', fn (): FakeCarrier => new FakeCarrier('first'));
    expect(Shipping::carrier('fake')->name())->toBe('first');

    Shipping::extend('fake', fn (): FakeCarrier => new FakeCarrier('second'));
    expect(Shipping::carrier('fake')->name())->toBe('second');
});

it('throws a clear exception for a missing carrier', function (): void {
    Shipping::carrier('gls');
})->throws(ShippingException::class, 'Instalează webrteu/gls');

it('throws when the creator does not return a carrier', function (): void {
    Shipping::extend('broken', fn (): stdClass => new stdClass);

    Shipping::carrier('broken');
})->throws(ShippingException::class, 'trebuie să implementeze contractul');

it('binds the manager as a singleton', function (): void {
    expect(app(ShippingManager::class))->toBe(app('shipping'));
});

it('exposes the fake optional capabilities', function (): void {
    $carrier = new FakeBulkCarrier;

    expect($carrier->pickupPoints()->first()?->type->value)->toBe('locker')
        ->and($carrier->orderPickup(new PickupRequestData(CarbonImmutable::now(), '10:00', '12:00', 1, 1.0)))->toBe('PICKUP-1')
        ->and($carrier->services()->first()?->code)->toBe('standard')
        ->and($carrier->label('AWB1'))->toBe('%PDF-fake')
        ->and($carrier->cancel('AWB1'))->toBeTrue();
});

it('quotes and creates shipments through the carrier contract', function (): void {
    Shipping::extend('fake', fn (): FakeCarrier => new FakeCarrier);
    $shipment = new ShipmentData(
        new AddressData('Ion Popescu', '0721000000', 'Brașov', 'Brașov', 'Str. Lungă', '10'),
        [new ParcelData(1)],
        'CMD-9',
    );

    $quote = Shipping::carrier('fake')->quote($shipment);
    $awb = Shipping::carrier('fake')->createShipment($shipment);

    expect($quote->amount)->toBe(1999)
        ->and($quote->currency)->toBe('RON')
        ->and($awb->awb)->toBe('AWB-CMD-9')
        ->and($awb->parcelNumbers)->toBe(['1']);
});
