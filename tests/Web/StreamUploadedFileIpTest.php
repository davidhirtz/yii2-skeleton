<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Web\StreamUploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;

class StreamUploadedFileIpTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function nonPublicIpProvider(): array
    {
        return [
            'loopback' => ['127.0.0.1'],
            'private' => ['10.0.0.1'],
            'link-local metadata' => ['169.254.169.254'],
            'shared address space' => ['100.64.0.1'],
            'alibaba metadata' => ['100.100.100.200'],
            'benchmarking' => ['198.18.0.1'],
            'ietf protocol assignments' => ['192.0.0.8'],
            'multicast' => ['224.0.0.1'],
            'ipv4 broadcast' => ['255.255.255.255'],
            'ipv6 loopback' => ['::1'],
            'ipv6 unique local' => ['fd00::1'],
            'ipv6 site-local' => ['fec0::1'],
            'ipv6 multicast' => ['ff02::1'],
            'ipv4-mapped metadata' => ['::ffff:169.254.169.254'],
            'ipv4-compatible metadata' => ['::a9fe:a9fe'],
            'nat64 metadata' => ['64:ff9b::a9fe:a9fe'],
            'local-use nat64' => ['64:ff9b:1::8.8.8.8'],
            'invalid' => ['not-an-ip'],
        ];
    }

    #[DataProvider('nonPublicIpProvider')]
    public function testRefusesANonPublicIp(string $ip): void
    {
        self::assertFalse($this->createUploadedFile()->isPublic($ip));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicIpProvider(): array
    {
        return [
            'ipv4' => ['8.8.8.8'],
            'ipv6' => ['2001:4860:4860::8888'],
            'ipv4-mapped public' => ['::ffff:8.8.8.8'],
            'nat64 public' => ['64:ff9b::808:808'],
        ];
    }

    #[DataProvider('publicIpProvider')]
    public function testAcceptsAPublicIp(string $ip): void
    {
        self::assertTrue($this->createUploadedFile()->isPublic($ip));
    }

    private function createUploadedFile(): IpStreamUploadedFile
    {
        return new IpStreamUploadedFile();
    }
}

class IpStreamUploadedFile extends StreamUploadedFile
{
    public function isPublic(string $ip): bool
    {
        return $this->isPublicIp($ip);
    }
}
