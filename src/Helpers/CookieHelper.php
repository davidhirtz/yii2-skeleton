<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\web\Cookie;

/**
 * A cookie's identity is its name *and* its scope, so a `Domain` the installation has since gained, lost or changed
 * leaves a twin of the same name that no scoped write ever reaches — and `$_COOKIE` keeps the *first* of the two the
 * browser sends, which is the stale one. {@see \yii\web\CookieCollection} is keyed by name alone, so the deletion
 * cannot go through it and is sent as a header of its own.
 */
class CookieHelper
{
    /**
     * A tenant writes `Domain=www.example.com`, a `cookieDomain` param `Domain=.example.com`, so the twin may sit
     * on any parent domain of the host as well as host-only (monorepo issue #555).
     *
     * @return list<string> a deletion for every scope at `$host` but the one `$cookie` is written under
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
