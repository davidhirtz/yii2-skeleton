<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp\OAuth;

use Hirtz\Skeleton\Mcp\OAuth\AuthorizationRequest;
use Hirtz\Skeleton\Mcp\OAuth\ClientMetadata;
use Hirtz\Skeleton\Mcp\OAuth\ClientMetadataFetcher;
use Hirtz\Skeleton\Mcp\OAuth\OAuthException;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;

class AuthorizationRequestTest extends TestCase
{
    #[DataProvider('clientIdDataProvider')]
    public function testAllowedClientIdsMatchUpToASlash(string $clientId, bool $allowed): void
    {
        $fetcher = new class () extends ClientMetadataFetcher {
            #[Override]
            public function fetch(string $clientId): ClientMetadata
            {
                // Reached only past the allow list
                throw new OAuthException(OAuthException::SERVER_ERROR);
            }
        };

        try {
            AuthorizationRequest::fromParams(['client_id' => $clientId], $fetcher, ['https://claude.ai', 'https://app.example.com/clients/']);
            self::fail('Neither refused nor fetched.');
        } catch (OAuthException $exception) {
            self::assertSame($allowed ? OAuthException::SERVER_ERROR : OAuthException::INVALID_CLIENT, $exception->error, $clientId);
        }
    }

    /**
     * @return list<array{string, bool}>
     */
    public static function clientIdDataProvider(): array
    {
        return [
            ['https://claude.ai/oauth/client.json', true],
            ['https://claude.ai.example.com/client.json', false],
            ['https://claude.aiexample.com/client.json', false],
            ['https://app.example.com/clients/mcp.json', true],
            ['https://app.example.com/clients-evil/mcp.json', false],
        ];
    }
}
