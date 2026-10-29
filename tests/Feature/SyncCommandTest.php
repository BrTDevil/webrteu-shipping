<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use WeBRTeu\Shipping\Enums\ShipmentStatus;
use WeBRTeu\Shipping\Events\ShipmentStatusChanged;
use WeBRTeu\Shipping\Facades\Shipping;
use WeBRTeu\Shipping\Models\Shipment;
use WeBRTeu\Shipping\Tests\Fakes\FakeBulkCarrier;
use WeBRTeu\Shipping\Tests\Fakes\FakeCarrier;

function shipment(string $awb, string $carrier = 'fake'): Shipment
{
    return Shipment::query()->create([
        'carrier' => $carrier,
        'awb' => $awb,
        'reference' => 'CMD-'.$awb,
        'status' => ShipmentStatus::Created,
        'cod_amount' => 0,
    ]);
}

function fakeCarrier(string $name = 'fake'): FakeCarrier
{
    $carrier = new FakeCarrier($name);
    Shipping::extend($name, fn (): FakeCarrier => $carrier);

    return $carrier;
}

function bulkCarrier(string $name = 'bulk'): FakeBulkCarrier
{
    $carrier = new FakeBulkCarrier($name);
    Shipping::extend($name, fn (): FakeBulkCarrier => $carrier);

    return $carrier;
}

beforeEach(function (): void {
    Event::fake([ShipmentStatusChanged::class]);
});

it('emits the event once when the status changes', function (): void {
    fakeCarrier()->willTrack('A1', ShipmentStatus::InTransit);
    shipment('A1');

    $this->artisan('shipping:sync')
        ->expectsOutput('Verificate: 1, schimbate: 1, erori: 0.')
        ->assertSuccessful();

    Event::assertDispatchedTimes(ShipmentStatusChanged::class, 1);
    expect(Shipment::query()->firstOrFail()->status)->toBe(ShipmentStatus::InTransit);
});

it('does not emit the event when the status stays the same', function (): void {
    fakeCarrier()->willTrack('A1', ShipmentStatus::Created);
    shipment('A1');

    $this->artisan('shipping:sync')->expectsOutput('Verificate: 1, schimbate: 0, erori: 0.');

    Event::assertNotDispatched(ShipmentStatusChanged::class);
});

it('skips shipments in a final state', function (): void {
    $carrier = fakeCarrier();
    shipment('A1')->forceFill(['status' => ShipmentStatus::Delivered])->save();

    $this->artisan('shipping:sync')->expectsOutput('Verificate: 0, schimbate: 0, erori: 0.');

    expect($carrier->tracked)->toBe([]);
});

it('skips shipments checked recently', function (): void {
    $carrier = fakeCarrier()->willTrack('A2', ShipmentStatus::InTransit);
    shipment('A1')->forceFill(['last_tracked_at' => now()->subMinutes(10)])->save();
    shipment('A2')->forceFill(['last_tracked_at' => now()->subMinutes(61)])->save();

    $this->artisan('shipping:sync');

    expect($carrier->tracked)->toBe(['A2']);
});

it('keeps going when one AWB fails', function (): void {
    Log::spy();
    $carrier = fakeCarrier()
        ->willTrack('A1', new RuntimeException('API down'))
        ->willTrack('A2', ShipmentStatus::Delivered);
    shipment('A1');
    shipment('A2');

    $this->artisan('shipping:sync')->expectsOutput('Verificate: 2, schimbate: 1, erori: 1.');

    expect($carrier->tracked)->toBe(['A1', 'A2']);
    Log::shouldHaveReceived('error')->once()->with('shipping.sync_failed', [
        'carrier' => 'fake',
        'awb' => 'A1',
        'error' => 'API down',
    ]);
});

it('uses trackMany when the carrier supports it, in batches', function (): void {
    Config::set('shipping.sync.batch_size', 2);
    $carrier = bulkCarrier()
        ->willTrack('B1', ShipmentStatus::InTransit)
        ->willTrack('B2', ShipmentStatus::Delivered)
        ->willTrack('B3', ShipmentStatus::Created);
    shipment('B1', 'bulk');
    shipment('B2', 'bulk');
    shipment('B3', 'bulk');
    shipment('B4', 'bulk');

    $this->artisan('shipping:sync')->expectsOutput('Verificate: 3, schimbate: 2, erori: 0.');

    expect($carrier->bulkCalls)->toBe([['B1', 'B2'], ['B3', 'B4']])
        ->and($carrier->tracked)->toBe([]);
});

it('logs a failed batch and continues with the next one', function (): void {
    Log::spy();
    $carrier = bulkCarrier();
    $carrier->bulkFailure = new RuntimeException('timeout');
    shipment('B1', 'bulk');
    shipment('B2', 'bulk');

    $this->artisan('shipping:sync')->expectsOutput('Verificate: 0, schimbate: 0, erori: 2.');

    Log::shouldHaveReceived('error')->once()->with('shipping.sync_failed', [
        'carrier' => 'bulk',
        'awb' => 'B1,B2',
        'error' => 'timeout',
    ]);
});

it('filters by carrier', function (): void {
    $fake = fakeCarrier();
    $bulk = bulkCarrier()->willTrack('B1', ShipmentStatus::InTransit);
    shipment('A1');
    shipment('B1', 'bulk');

    $this->artisan('shipping:sync', ['--carrier' => 'bulk']);

    expect($fake->tracked)->toBe([])
        ->and($bulk->bulkCalls)->toBe([['B1']]);
});

it('syncs a single AWB regardless of the last check', function (): void {
    $carrier = fakeCarrier()->willTrack('A1', ShipmentStatus::InTransit);
    shipment('A1')->forceFill(['last_tracked_at' => now()])->save();
    shipment('A2');

    $this->artisan('shipping:sync', ['--awb' => 'A1'])->expectsOutput('Verificate: 1, schimbate: 1, erori: 0.');

    expect($carrier->tracked)->toBe(['A1']);
});

it('warns about shipments of carriers that are not installed', function (): void {
    shipment('G1', 'gls');

    $this->artisan('shipping:sync')
        ->expectsOutputToContain('Curierul [gls] nu este înregistrat')
        ->expectsOutput('Verificate: 0, schimbate: 0, erori: 1.')
        ->assertSuccessful();
});
