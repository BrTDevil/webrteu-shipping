<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Data;

final readonly class AddressData
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $county,
        public string $city,
        public string $street,
        public string $number,
        public ?string $email = null,
        public ?string $contactPerson = null,
        public ?string $block = null,
        public ?string $staircase = null,
        public ?string $floor = null,
        public ?string $apartment = null,
        public ?string $postalCode = null,
        public string $countryCode = 'RO',
        public ?string $company = null,
    ) {}

    /**
     * Adresa pe un singur rând: „Str. Lungă nr. 10, bl. A1, sc. 2, et. 3, ap. 12, Brașov, jud. Brașov, 500001”.
     */
    public function line(): string
    {
        $parts = array_filter([
            trim($this->street.' nr. '.$this->number),
            $this->block !== null ? 'bl. '.$this->block : null,
            $this->staircase !== null ? 'sc. '.$this->staircase : null,
            $this->floor !== null ? 'et. '.$this->floor : null,
            $this->apartment !== null ? 'ap. '.$this->apartment : null,
            $this->city,
            'jud. '.$this->county,
            $this->postalCode,
        ], fn (?string $part): bool => $part !== null && $part !== '');

        return implode(', ', $parts);
    }

    /**
     * Persoana de contact și telefonul: „Ion Popescu (SC Exemplu SRL), 0721000000”.
     */
    public function contact(): string
    {
        $person = $this->contactPerson ?? $this->name;

        if ($this->company !== null && $this->company !== $person) {
            $person .= ' ('.$this->company.')';
        }

        return $person.', '.$this->phone;
    }
}
