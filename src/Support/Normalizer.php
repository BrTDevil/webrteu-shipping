<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Support;

use Illuminate\Support\Str;

/**
 * Normalizare pentru localități, județe și telefoane din România.
 */
final class Normalizer
{
    /**
     * Prefixe administrative eliminate la comparare (după transformarea în ASCII lowercase).
     */
    private const PREFIXES = [
        'municipiul', 'municipiu', 'mun.', 'mun',
        'orasul', 'oras', 'or.',
        'comuna', 'com.', 'com',
        'satul', 'sat',
        'judetul', 'judet', 'jud.', 'jud',
    ];

    /**
     * Elimină diacriticele (inclusiv variantele cu sedilă ş/ţ), păstrând majusculele.
     */
    public static function ascii(string $value): string
    {
        return Str::ascii(strtr($value, [
            'ş' => 's', 'Ş' => 'S', 'ţ' => 't', 'Ţ' => 'T',
            'ș' => 's', 'Ș' => 'S', 'ț' => 't', 'Ț' => 'T',
        ]));
    }

    /**
     * Cheie de comparare: „Municipiul Brașov” → „brasov”, „Cluj-Napoca” → „clujnapoca”.
     */
    public static function key(string $value): string
    {
        $value = strtolower(self::ascii(trim($value)));
        $value = (string) preg_replace('/\s+/', ' ', $value);

        foreach (self::PREFIXES as $prefix) {
            if (str_starts_with($value, $prefix.' ') || (str_ends_with($prefix, '.') && str_starts_with($value, $prefix))) {
                $value = substr($value, strlen($prefix));

                break;
            }
        }

        return (string) preg_replace('/[^a-z0-9]/', '', $value);
    }

    public static function same(string $first, string $second): bool
    {
        return self::key($first) === self::key($second);
    }

    /**
     * „+40 721 000 000”, „0040721000000”, „40721000000”, „721000000” → „0721000000”.
     */
    public static function phone(string $value): string
    {
        $digits = (string) preg_replace('/\D/', '', $value);

        return match (true) {
            str_starts_with($digits, '0040') => '0'.substr($digits, 4),
            str_starts_with($digits, '40') && strlen($digits) === 11 => '0'.substr($digits, 2),
            strlen($digits) === 9 && ! str_starts_with($digits, '0') => '0'.$digits,
            default => $digits,
        };
    }
}
