<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Exceptions;

use Throwable;

class ShipmentValidationException extends ShippingException
{
    /**
     * @param  array<string, list<string>>  $errors  câmp → mesaje de eroare, așa cum le întoarce curierul
     */
    public function __construct(string $message = '', public readonly array $errors = [], ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    public static function withErrors(string $carrier, array $errors, ?Throwable $previous = null): self
    {
        $details = implode('; ', array_map(
            fn (string $field, array $messages): string => $field.': '.implode(', ', $messages),
            array_keys($errors),
            $errors,
        ));

        return new self(sprintf('Curierul [%s] a respins expedierea: %s', $carrier, $details), $errors, $previous);
    }
}
