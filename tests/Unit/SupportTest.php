<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use WeBRTeu\Shipping\Data\PickupPointData;
use WeBRTeu\Shipping\Data\PickupRequestData;
use WeBRTeu\Shipping\Enums\PickupPointType;
use WeBRTeu\Shipping\Exceptions\AuthenticationException;
use WeBRTeu\Shipping\Exceptions\ShipmentStateUnknownException;
use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;
use WeBRTeu\Shipping\Exceptions\UnsupportedOperationException;
use WeBRTeu\Shipping\Support\CachedToken;

it('remembers the token until it is forgotten', function (): void {
    $logins = 0;
    $login = function () use (&$logins): string {
        $logins++;

        return 'token-'.$logins;
    };

    expect(CachedToken::remember('fake.token', 60, $login))->toBe('token-1')
        ->and(CachedToken::remember('fake.token', 60, $login))->toBe('token-1');

    CachedToken::forget('fake.token');

    expect(CachedToken::remember('fake.token', 60, $login))->toBe('token-2')
        ->and($logins)->toBe(2);
});

it('rejects an empty token and does not cache it', function (): void {
    expect(fn (): string => CachedToken::remember('fake.token', 60, fn (): string => ''))
        ->toThrow(AuthenticationException::class);

    expect(Cache::has('fake.token'))->toBeFalse();
});

it('describes unsupported operations and unknown states', function (): void {
    $previous = new RuntimeException('timeout');
    $unknown = new ShipmentStateUnknownException('fake', 'CMD-1', $previous);

    expect(UnsupportedOperationException::for('fake', 'pickupPoints')->getMessage())->toBe('Curierul [fake] nu suportă operația [pickupPoints].')
        ->and($unknown->reference)->toBe('CMD-1')
        ->and($unknown->carrier)->toBe('fake')
        ->and($unknown->getPrevious())->toBe($previous)
        ->and($unknown->getMessage())->toContain('CMD-1');
});

it('exports a pickup point as an array', function (): void {
    $point = new PickupPointData('7', 'fake', PickupPointType::Locker, 'easybox Mall', 'Brașov', 'Brașov', 'Str. Lungă 1', 45.65, 25.6, '500001', true, ['luni' => '08-20']);

    expect($point->toArray())->toBe([
        'id' => '7',
        'carrier' => 'fake',
        'type' => 'locker',
        'name' => 'easybox Mall',
        'county' => 'Brașov',
        'city' => 'Brașov',
        'address' => 'Str. Lungă 1',
        'lat' => 45.65,
        'lng' => 25.6,
        'postal_code' => '500001',
        'supports_cod' => true,
        'schedule' => ['luni' => '08-20'],
    ]);
});

it('holds a pickup request', function (): void {
    $request = new PickupRequestData(CarbonImmutable::parse('2026-01-05'), '10:00', '16:00', 3, 4.5);

    expect($request->parcels)->toBe(3)
        ->and($request->address)->toBeNull();
});

it('carries the validation errors per field', function (): void {
    $previous = new RuntimeException('400');
    $exception = ShipmentValidationException::withErrors('fake', [
        'awbRecipient.phoneNumber' => ['Telefon invalid'],
        'parcels' => ['Greutate lipsă', 'Prea mare'],
    ], $previous);

    expect($exception->errors)->toHaveKeys(['awbRecipient.phoneNumber', 'parcels'])
        ->and($exception->getMessage())->toBe('Curierul [fake] a respins expedierea: awbRecipient.phoneNumber: Telefon invalid; parcels: Greutate lipsă, Prea mare')
        ->and($exception->getPrevious())->toBe($previous)
        ->and((new ShipmentValidationException('x'))->errors)->toBe([]);
});
