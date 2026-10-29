<?php

declare(strict_types=1);
use Illuminate\Database\Eloquent\Model;
use WeBRTeu\Shipping\Exceptions\ShippingException;

arch('nu folosește funcții de debug')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'die'])
    ->not->toBeUsed();

arch('toate fișierele au strict types')
    ->expect('WeBRTeu\Shipping')
    ->toUseStrictTypes();

arch('DTO-urile sunt final readonly')
    ->expect('WeBRTeu\Shipping\Data')
    ->toBeFinal()
    ->toBeReadonly();

arch('contractele sunt interfețe')
    ->expect('WeBRTeu\Shipping\Contracts')
    ->toBeInterfaces();

arch('enum-urile sunt backed enums')
    ->expect('WeBRTeu\Shipping\Enums')
    ->toBeStringBackedEnums();

arch('modelele extind Eloquent Model')
    ->expect('WeBRTeu\Shipping\Models')
    ->toExtend(Model::class);

arch('excepțiile extind ShippingException')
    ->expect('WeBRTeu\Shipping\Exceptions')
    ->toExtend(ShippingException::class)
    ->ignoring(ShippingException::class);

arch('HTTP doar prin facade-ul Laravel, fără Guzzle direct')
    ->expect('WeBRTeu\Shipping')
    ->not->toUse('GuzzleHttp');
