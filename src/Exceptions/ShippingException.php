<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Exceptions;

use RuntimeException;
use WeBRTeu\Shipping\Contracts\Carrier;

class ShippingException extends RuntimeException
{
    public static function carrierNotRegistered(string $carrier): self
    {
        return new self(sprintf(
            'Curierul [%s] nu este înregistrat. Instalează webrteu/%s sau înregistrează-l cu Shipping::extend().',
            $carrier,
            $carrier,
        ));
    }

    public static function invalidCarrier(string $carrier): self
    {
        return new self(sprintf('Curierul [%s] trebuie să implementeze contractul %s.', $carrier, Carrier::class));
    }
}
