<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Helpers;

class NetworkHelper
{
    /**
     * @return list<string>
     */
    public static function resolveHost(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (isset($record['ipv6']) && is_string($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        return $ips;
    }

    /**
     * `FILTER_FLAG_GLOBAL_RANGE` lets multicast, IPv4-compatible, local-use NAT64 and site-local IPv6 addresses
     * through, so those are refused first; mapped and NAT64 addresses are checked as the IPv4 they reach.
     */
    public static function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 16) {
            if (
                str_starts_with($packed, str_repeat("\0", 10) . "\xff\xff")
                || str_starts_with($packed, "\0\x64\xff\x9b" . str_repeat("\0", 8))
            ) {
                $packed = substr($packed, 12);
            } elseif (
                str_starts_with($packed, str_repeat("\0", 12))
                || str_starts_with($packed, "\0\x64\xff\x9b\0\x01")
                || $packed[0] === "\xff"
                || ($packed[0] === "\xfe" && (ord($packed[1]) & 0xc0) === 0xc0)
            ) {
                return false;
            }
        }

        if (strlen($packed) === 4 && (ord($packed[0]) & 0xf0) === 0xe0) {
            return false;
        }

        return (bool)filter_var(
            inet_ntop($packed),
            FILTER_VALIDATE_IP,
            FILTER_FLAG_GLOBAL_RANGE | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
