<?php

declare(strict_types=1);

use WeBRTeu\Shipping\Support\Normalizer;

it('builds comparison keys for Romanian localities', function (string $value, string $expected): void {
    expect(Normalizer::key($value))->toBe($expected);
})->with([
    ['Municipiul Brașov', 'brasov'],
    ['Mun. Brașov', 'brasov'],
    ['mun.Brasov', 'brasov'],
    ['Județul Cluj', 'cluj'],
    ['Jud. Cluj', 'cluj'],
    ['Comuna Șercaia', 'sercaia'],
    ['Oraș Codlea', 'codlea'],
    ['Cluj-Napoca', 'clujnapoca'],
    ['Sector 1', 'sector1'],
    ['  Satu   Mare ', 'satumare'],
    ['Comănești', 'comanesti'],
]);

it('treats comma and cedilla diacritics as the same letter', function (): void {
    expect(Normalizer::same('Șercaia', 'Şercaia'))->toBeTrue()
        ->and(Normalizer::same('Târgu Mureș', 'TARGU MURES'))->toBeTrue()
        ->and(Normalizer::same('Brașov', 'Bacău'))->toBeFalse();
});

it('strips diacritics but keeps the case', function (): void {
    expect(Normalizer::ascii('Țară Șercaia Ţeţ Ştefan Ă Î Â'))->toBe('Tara Sercaia Tet Stefan A I A');
});

it('normalizes Romanian phone numbers', function (string $value, string $expected): void {
    expect(Normalizer::phone($value))->toBe($expected);
})->with([
    ['+40 721 000 000', '0721000000'],
    ['0040721000000', '0721000000'],
    ['40721000000', '0721000000'],
    ['0721-000-000', '0721000000'],
    ['721000000', '0721000000'],
    ['+44 20 7946 0958', '442079460958'],
]);
