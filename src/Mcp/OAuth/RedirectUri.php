<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

class RedirectUri
{
    private const array LOOPBACK_HOSTS = ['localhost', '127.0.0.1', '[::1]'];

    /**
     * Exact, except that a native client's loopback redirect matches on any port (RFC 8252 §7.3), `localhost`
     * included because Claude Code registers it.
     */
    public static function matches(string $registered, string $requested): bool
    {
        if ($registered === $requested) {
            return true;
        }

        $a = parse_url($registered);
        $b = parse_url($requested);

        if (!is_array($a) || !is_array($b) || !self::isLoopback($registered) || isset($b['fragment'])) {
            return false;
        }

        foreach (['scheme', 'host', 'path', 'query', 'user', 'pass'] as $part) {
            if (($a[$part] ?? null) !== ($b[$part] ?? null)) {
                return false;
            }
        }

        return true;
    }

    public static function isLoopback(string $uri): bool
    {
        return parse_url($uri, PHP_URL_SCHEME) === 'http'
            && in_array(strtolower((string)parse_url($uri, PHP_URL_HOST)), self::LOOPBACK_HOSTS, true);
    }

    /**
     * A redirect URI a client may register: absolute, without a fragment, HTTPS unless it is a loopback address.
     */
    public static function isValid(string $uri): bool
    {
        $parts = parse_url($uri);

        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || isset($parts['fragment'])) {
            return false;
        }

        return $parts['scheme'] === 'https' || self::isLoopback($uri);
    }
}
