<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

use yii\web\Cookie;

/**
 * A cookie's identity is its name *and* its scope, so a `Domain` the installation has since gained or lost leaves
 * a host-only twin of the same name that no scoped write ever reaches — and `$_COOKIE` keeps the *first* of the
 * two the browser sends, which is the stale one. {@see \yii\web\CookieCollection} is keyed by name alone, so the
 * deletion cannot go through it and is sent as a header of its own.
 */
class CookieHelper
{
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
