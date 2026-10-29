<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Tests\Fakes;

use Illuminate\Support\Collection;
use Throwable;
use WeBRTeu\Shipping\Contracts\Carrier;
use WeBRTeu\Shipping\Data\AwbData;
use WeBRTeu\Shipping\Data\QuoteData;
use WeBRTeu\Shipping\Data\ServiceData;
use WeBRTeu\Shipping\Data\ShipmentData;
use WeBRTeu\Shipping\Data\TrackingData;
use WeBRTeu\Shipping\Enums\LabelFormat;
use WeBRTeu\Shipping\Enums\ShipmentStatus;

/**
 * Curier fals doar cu contractul de bază, cu răspunsuri de tracking programabile.
 */
class FakeCarrier implements Carrier
{
    /**
     * @var array<string, TrackingData|Throwable>
     */
    public array $tracking = [];

    /**
     * @var list<string>
     */
    public array $tracked = [];

    public function __construct(private readonly string $name = 'fake') {}

    public function name(): string
    {
        return $this->name;
    }

    public function willTrack(string $awb, ShipmentStatus|Throwable $result, string $label = ''): static
    {
        $this->tracking[$awb] = $result instanceof Throwable
            ? $result
            : new TrackingData($awb, $result, $label !== '' ? $label : $result->label());

        return $this;
    }

    public function services(): Collection
    {
        return collect([new ServiceData('standard', 'Standard')]);
    }

    public function quote(ShipmentData $shipment): QuoteData
    {
        return new QuoteData($this->name, 1999);
    }

    public function createShipment(ShipmentData $shipment): AwbData
    {
        return new AwbData('AWB-'.$shipment->reference, $this->name, 1999, ['1']);
    }

    public function label(string $awb, LabelFormat $format = LabelFormat::A6Pdf): string
    {
        return '%PDF-fake';
    }

    public function track(string $awb): TrackingData
    {
        $this->tracked[] = $awb;

        return $this->result($awb);
    }

    public function cancel(string $awb): bool
    {
        return true;
    }

    protected function result(string $awb): TrackingData
    {
        $result = $this->tracking[$awb] ?? new TrackingData($awb, ShipmentStatus::Unknown, '');

        if ($result instanceof Throwable) {
            throw $result;
        }

        return $result;
    }
}
