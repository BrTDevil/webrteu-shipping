<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Exceptions;

class UnsupportedOperationException extends ShippingException
{
    public static function for(string $carrier, string $operation): self
    {
        return new self(sprintf('Curierul [%s] nu suportă operația [%s].', $carrier, $operation));
    }
}
