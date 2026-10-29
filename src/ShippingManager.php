<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Shipping\Contracts\Carrier;
use WeBRTeu\Shipping\Exceptions\ShippingException;

class ShippingManager
{
    /**
     * @var array<string, Closure(Container): mixed>
     */
    private array $creators = [];

    /**
     * @var array<string, Carrier>
     */
    private array $carriers = [];

    public function __construct(private readonly Container $container) {}

    public function carrier(?string $name = null): Carrier
    {
        $name ??= $this->getDefaultCarrier();

        return $this->carriers[$name] ??= $this->resolve($name);
    }

    /**
     * Înregistrează un curier; apelat din service provider-ul fiecărui pachet de curier.
     *
     * @param  Closure(Container): mixed  $creator
     */
    public function extend(string $name, Closure $creator): static
    {
        $this->creators[$name] = $creator;
        unset($this->carriers[$name]);

        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->creators[$name]);
    }

    /**
     * @return list<string>
     */
    public function available(): array
    {
        return array_keys($this->creators);
    }

    public function getDefaultCarrier(): string
    {
        return Config::string('shipping.default', 'sameday');
    }

    private function resolve(string $name): Carrier
    {
        if (! isset($this->creators[$name])) {
            throw ShippingException::carrierNotRegistered($name);
        }

        $carrier = ($this->creators[$name])($this->container);

        if (! $carrier instanceof Carrier) {
            throw ShippingException::invalidCarrier($name);
        }

        return $carrier;
    }
}
