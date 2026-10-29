<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

use WeBRTeu\Shipping\Exceptions\ShipmentValidationException;

final readonly class ShipmentData
{
    /**
     * @param  list<ParcelData>  $parcels
     * @param  int  $codAmount  ramburs, în bani
     * @param  int  $declaredValue  valoare declarată, în bani
     * @param  AddressData|null  $sender  null = punctul de ridicare din configurarea curierului
     */
    public function __construct(
        public AddressData $recipient,
        public array $parcels,
        public string $reference,
        public ?string $serviceCode = null,
        public int $codAmount = 0,
        public int $declaredValue = 0,
        public string $contents = '',
        public ?AddressData $sender = null,
        public ?string $pickupPointId = null,
        public bool $openPackage = false,
        public bool $saturdayDelivery = false,
        public ?string $observations = null,
        public int $envelopes = 0,
    ) {
        if ($parcels === [] && $envelopes === 0) {
            throw new ShipmentValidationException('Expedierea trebuie să conțină cel puțin un colet sau un plic.');
        }

        if ($envelopes < 0) {
            throw new ShipmentValidationException('Numărul de plicuri nu poate fi negativ.');
        }

        if ($codAmount < 0 || $declaredValue < 0) {
            throw new ShipmentValidationException('Rambursul și valoarea declarată nu pot fi negative.');
        }
    }

    public function totalWeightKg(): float
    {
        return round(array_sum(array_map(fn (ParcelData $parcel): float => $parcel->weightKg, $this->parcels)), 3);
    }

    public function parcelCount(): int
    {
        return count($this->parcels);
    }

    public function isPickupPointDelivery(): bool
    {
        return $this->pickupPointId !== null && $this->pickupPointId !== '';
    }

    public function hasCashOnDelivery(): bool
    {
        return $this->codAmount > 0;
    }
}
