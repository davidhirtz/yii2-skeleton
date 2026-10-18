<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp\OAuth;

use Hirtz\Skeleton\Mcp\OAuth\RedirectUri;
use Hirtz\Skeleton\Mcp\OAuth\ResourceUri;
use Hirtz\Skeleton\Test\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RedirectUriTest extends TestCase
{
    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function matchProvider(): array
    {
        return [
            'exact' => ['https://claude.ai/api/mcp/auth_callback', 'https://claude.ai/api/mcp/auth_callback', true],
            'other path' => ['https://claude.ai/api/mcp/auth_callback', 'https://claude.ai/api/mcp/auth_callback/x', false],
            'prefix' => ['https://claude.ai/api/mcp/', 'https://claude.ai/api/mcp/auth_callback', false],
            'https port' => ['https://claude.ai/callback', 'https://claude.ai:8443/callback', false],
            'path case' => ['https://claude.ai/Callback', 'https://claude.ai/callback', false],
            'localhost any port' => ['http://localhost/callback', 'http://localhost:3118/callback', true],
            'ip any port' => ['http://127.0.0.1/callback', 'http://127.0.0.1:52011/callback', true],
            'ipv6 any port' => ['http://[::1]/callback', 'http://[::1]:52011/callback', true],
            'loopback other path' => ['http://localhost/callback', 'http://localhost:3118/other', false],
            'loopback other host' => ['http://localhost/callback', 'http://127.0.0.1:3118/callback', false],
            'loopback https' => ['http://localhost/callback', 'https://localhost:3118/callback', false],
            'loopback fragment' => ['http://localhost/callback', 'http://localhost:3118/callback#x', false],
            'loopback query' => ['http://localhost/callback', 'http://localhost:3118/callback?x=1', false],
        ];
    }

    #[DataProvider('matchProvider')]
    public function testMatches(string $registered, string $requested, bool $expected): void
    {
        self::assertSame($expected, RedirectUri::matches($registered, $requested));
    }

    public function testIsValid(): void
    {
        self::assertTrue(RedirectUri::isValid('https://claude.ai/api/mcp/auth_callback'));
        self::assertTrue(RedirectUri::isValid('http://localhost/callback'));
        self::assertFalse(RedirectUri::isValid('http://claude.ai/callback'));
        self::assertFalse(RedirectUri::isValid('https://claude.ai/callback#x'));
        self::assertFalse(RedirectUri::isValid('/callback'));
        self::assertFalse(RedirectUri::isValid('javascript:alert(1)'));
    }

    public function testResourceEquality(): void
    {
        self::assertTrue(ResourceUri::equals('https://www.example.com/mcp', 'HTTPS://WWW.Example.com/mcp/'));
        self::assertFalse(ResourceUri::equals('https://www.example.com/mcp', 'https://www.example.com/MCP'));
        self::assertFalse(ResourceUri::equals('https://www.example.com/mcp', 'https://example.com/mcp'));
        self::assertFalse(ResourceUri::equals('https://www.example.com/mcp', 'https://www.example.com:8443/mcp'));
    }
}
