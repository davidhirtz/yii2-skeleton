<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

class ResourceUri
{
    /**
     * RFC 8707 resource indicators, compared with the scheme and host case-folded and a trailing slash dropped.
     */
    public static function equals(string $a, string $b): bool
    {
        return self::normalize($a) === self::normalize($b);
    }

    public static function normalize(string $uri): string
    {
        $parts = parse_url($uri);

        if (!is_array($parts) || !isset($parts['scheme'], $parts['host']) || isset($parts['fragment'])) {
            return $uri;
        }

        $port = isset($parts['port']) ? ":{$parts['port']}" : '';
        $query = isset($parts['query']) ? "?{$parts['query']}" : '';

        return strtolower($parts['scheme']) . '://' . strtolower($parts['host']) . $port
            . rtrim($parts['path'] ?? '', '/') . $query;
    }
}
