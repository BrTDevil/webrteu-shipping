<?php

declare(strict_types=1);

use WeBRTeu\Shipping\Support\Money;

it('formats bani as a decimal string', function (int $amount, string $expected): void {
    expect(Money::toDecimalString($amount))->toBe($expected);
})->with([
    [14170, '141.70'],
    [5, '0.05'],
    [-250, '-2.50'],
    [0, '0.00'],
    [100, '1.00'],
]);

it('converts bani to float', function (): void {
    expect(Money::toFloat(14170))->toBe(141.7)
        ->and(Money::toFloat(-5))->toBe(-0.05);
});

it('parses decimal values into bani', function (string|int $value, int $expected): void {
    expect(Money::fromDecimal($value))->toBe($expected);
})->with([
    ['141,70', 14170],
    ['141,7', 14170],
    ['141.70', 14170],
    ['1.005', 101],
    ['1.004', 100],
    ['-2.5', -250],
    ['1.234,56', 123456],
    ['1,234.56', 123456],
    [' 12 ', 1200],
    [12, 1200],
]);

it('rejects invalid decimal values', function (string $value): void {
    Money::fromDecimal($value);
})->with(['abc', '', '1.2.3x', '--1'])->throws(InvalidArgumentException::class);
