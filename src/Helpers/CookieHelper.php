<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\web\Cookie;

/**
 * A twin under a stale `Domain` wins `$_COOKIE`, and `CookieCollection` is keyed by name alone, so its deletion is a
 * header of its own.
 */
class CookieHelper
{
    /**
     * A tenant writes `Domain=www.example.com`, a `cookieDomain` param `.example.com`, so the twin may sit host-only
     * or on any parent domain (#555); the scope `$cookie` is written under is left out.
     *
     * @return list<string>
     */
    public static function getExpiredTwinHeaders(Cookie $cookie, string $host): array
    {
        $domain = strtolower(ltrim($cookie->domain, '.'));
        $headers = $domain !== '' ? [static::getExpiredHeader($cookie)] : [];

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $headers;
        }

        $labels = explode('.', strtolower($host));

        while (count($labels) > 1) {
            $parent = implode('.', $labels);

            if ($parent !== $domain) {
                $headers[] = static::getExpiredHeader($cookie) . "; Domain=$parent";
            }

            array_shift($labels);
        }

        return $headers;
    }

    public static function getExpiredHeader(Cookie $cookie): string
    {
        $parts = [
            "$cookie->name=",
            'Expires=Thu, 01 Jan 1970 00:00:01 GMT',
            'Max-Age=0',
            'Path=' . ($cookie->path ?: '/'),
        ];

        if ($cookie->secure) {
            $parts[] = 'Secure';
        }

        if ($cookie->httpOnly) {
            $parts[] = 'HttpOnly';
        }

        if ($cookie->sameSite) {
            $parts[] = "SameSite=$cookie->sameSite";
        }

        return implode('; ', $parts);
    }
}
