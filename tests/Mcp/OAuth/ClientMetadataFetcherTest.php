<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp\OAuth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Hirtz\Skeleton\Mcp\OAuth\ClientMetadata;
use Hirtz\Skeleton\Mcp\OAuth\ClientMetadataFetcher;
use Hirtz\Skeleton\Mcp\OAuth\OAuthException;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use Yii;

class ClientMetadataFetcherTest extends TestCase
{
    private const string CLIENT_ID = 'https://claude.ai/oauth/mcp-oauth-client-metadata';

    private MockHandler $handler;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Yii::$app->getCache()?->delete([ClientMetadataFetcher::class, self::CLIENT_ID]);
        $this->handler = new MockHandler();
    }

    public function testAValidDocumentIsReadAndCached(): void
    {
        $this->handler->append(new Response(200, ['Cache-Control' => 'max-age=600'], $this->getDocument([
            'client_name' => 'Claude 日本語 🤖',
        ])));

        $metadata = $this->createFetcher()->fetch(self::CLIENT_ID);

        self::assertSame(self::CLIENT_ID, $metadata->clientId);
        self::assertSame('Claude 日本語 🤖', $metadata->clientName);
        self::assertTrue($metadata->allowsRedirectUri('https://claude.ai/api/mcp/auth_callback'));
        self::assertTrue($metadata->allowsRedirectUri('http://localhost:4123/callback'));
        self::assertFalse($metadata->allowsRedirectUri('https://evil.example/callback'));

        // the second fetch is answered by the cache, the empty handler would throw
        self::assertSame(self::CLIENT_ID, $this->createFetcher()->fetch(self::CLIENT_ID)->clientId);
        self::assertSame('GET', $this->handler->getLastRequest()?->getMethod());
    }

    public function testANoStoreDocumentIsFetchedAgain(): void
    {
        $this->handler->append(
            new Response(200, ['Cache-Control' => 'no-store'], $this->getDocument()),
            new Response(200, [], $this->getDocument()),
        );

        $this->createFetcher()->fetch(self::CLIENT_ID);
        $this->createFetcher()->fetch(self::CLIENT_ID);

        self::assertSame(0, $this->handler->count());
    }

    public function testAnotherClientIdIsRefused(): void
    {
        $this->handler->append(new Response(200, [], $this->getDocument(['client_id' => 'https://evil.example/client'])));
        $this->assertRefused();
    }

    public function testAConfidentialClientIsRefused(): void
    {
        $this->handler->append(new Response(200, [], $this->getDocument(['token_endpoint_auth_method' => 'client_secret_basic'])));
        $this->assertRefused();
    }

    public function testAnInsecureRedirectUriIsRefused(): void
    {
        $this->handler->append(new Response(200, [], $this->getDocument(['redirect_uris' => ['http://claude.ai/callback']])));
        $this->assertRefused();
    }

    public function testAnOversizedDocumentIsRefused(): void
    {
        $this->handler->append(new Response(200, [], $this->getDocument(['client_name' => str_repeat('x', 6000)])));
        $this->assertRefused();
    }

    public function testARedirectIsNotFollowed(): void
    {
        $this->handler->append(new Response(302, ['Location' => 'http://169.254.169.254/']));
        $this->assertRefused();
    }

    public function testATimeoutIsRefused(): void
    {
        $this->handler->append(new ConnectException('Timed out', new Request('GET', self::CLIENT_ID)));
        $this->assertRefused();
    }

    public function testAnInvalidJsonIsRefused(): void
    {
        $this->handler->append(new Response(200, [], 'not json'));
        $this->assertRefused();
    }

    public function testAPrivateAddressIsNotFetched(): void
    {
        $fetcher = new ClientMetadataFetcher();
        $fetcher->client = new Client(['handler' => HandlerStack::create($this->handler)]);

        $this->expectException(OAuthException::class);
        $fetcher->fetch('https://127.0.0.1/client');
    }

    public function testClientIdValidation(): void
    {
        self::assertTrue(ClientMetadataFetcher::isValidClientId(self::CLIENT_ID));
        self::assertFalse(ClientMetadataFetcher::isValidClientId('https://claude.ai'));
        self::assertFalse(ClientMetadataFetcher::isValidClientId('https://claude.ai/'));
        self::assertFalse(ClientMetadataFetcher::isValidClientId('http://claude.ai/client'));
        self::assertFalse(ClientMetadataFetcher::isValidClientId('https://user@claude.ai/client'));
        self::assertFalse(ClientMetadataFetcher::isValidClientId('https://claude.ai/client#x'));
        self::assertFalse(ClientMetadataFetcher::isValidClientId('client-id'));
    }

    public function testTheNameIsTrimmedToTheColumn(): void
    {
        $metadata = ClientMetadata::fromArray(self::CLIENT_ID, json_decode($this->getDocument([
            'client_name' => ' ' . str_repeat('Mu' . "\u{308}" . 'ller', 30),
        ]), true, flags: JSON_THROW_ON_ERROR));

        self::assertSame(100, mb_strlen((string)$metadata->clientName));
        self::assertStringStartsWith("Mu\u{308}ller", (string)$metadata->clientName);
    }

    private function assertRefused(): void
    {
        $this->expectException(OAuthException::class);
        $this->createFetcher()->fetch(self::CLIENT_ID);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getDocument(array $data = []): string
    {
        return json_encode([
            'client_id' => self::CLIENT_ID,
            'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback', 'http://localhost/callback'],
            'token_endpoint_auth_method' => 'none',
            ...$data,
        ], JSON_THROW_ON_ERROR);
    }

    private function createFetcher(): ClientMetadataFetcher
    {
        $fetcher = new PublicClientMetadataFetcher();
        $fetcher->client = new Client(['handler' => HandlerStack::create($this->handler)]);

        return $fetcher;
    }
}

class PublicClientMetadataFetcher extends ClientMetadataFetcher
{
    #[Override]
    protected function resolvePublicIp(string $host): ?string
    {
        return '160.79.104.10';
    }
}
