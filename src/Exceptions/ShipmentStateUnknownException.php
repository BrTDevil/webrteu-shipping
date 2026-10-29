<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Exceptions;

use Throwable;

/**
 * Aruncată când crearea unui AWB a expirat (timeout) și nu se știe dacă AWB-ul există.
 * Aplicația trebuie să verifice manual în contul curierului înainte de a reîncerca.
 */
class ShipmentStateUnknownException extends ShippingException
{
    public function __construct(
        public readonly string $carrier,
        public readonly string $reference,
        ?Throwable $previous = null,
    ) {
        parent::__construct(sprintf(
            'Starea expedierii pentru comanda [%s] la [%s] este necunoscută (timeout). Verifică manual în contul curierului înainte de a reîncerca.',
            $reference,
            $carrier,
        ), 0, $previous);
    }
}
