<?php

declare(strict_types=1);

namespace WeBRTeu\Shipping\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use WeBRTeu\Shipping\Exceptions\AuthenticationException;

/**
 * Token de sesiune păstrat în cache, pentru curierii care cer login (FAN, Cargus, Sameday).
 * TTL-ul trebuie să fie mai mic decât expirarea oficială a token-ului.
 */
final class CachedToken
{
    /**
     * @param  Closure(): string  $login
     */
    public static function remember(string $key, int $ttl, Closure $login): string
    {
        $token = Cache::remember($key, $ttl, $login);

        if ($token === '') {
            Cache::forget($key);

            throw new AuthenticationException(sprintf('Autentificarea pentru [%s] nu a întors un token valid.', $key));
        }

        return $token;
    }

    public static function forget(string $key): void
    {
        Cache::forget($key);
    }
}
