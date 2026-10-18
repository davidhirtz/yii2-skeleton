<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Server;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\Browser;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Request;
use Hirtz\Skeleton\Web\User as WebUser;
use Override;

class McpEndpointTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config['components']['mcp'] = [
            'enableHttp' => true,
            'authenticationAttemptLimit' => 2,
            'allowedOrigins' => ['https://claude.ai'],
        ];

        $this->reloadApplication();

        self::$client = new Browser(['HTTP_HOST' => 'www.test.localhost', 'HTTPS' => 'on']);
    }

    public function testWithoutATokenTheRequestIsUnauthorized(): void
    {
        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('www-authenticate', 'Bearer');
    }

    public function testBadTokensAreCountedPerAddress(): void
    {
        foreach ([401, 401, 429] as $status) {
            $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], 'wrong');
            self::assertResponseStatusCodeSame($status);
        }

        // the clients of a remote connector share its addresses, so a valid token still passes
        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $this->issueToken());
        self::assertResponseStatusCodeSame(200);
    }

    public function testAPersonalTokenRecordsItsLastUse(): void
    {
        $token = $this->issueToken();
        self::assertNull(UserToken::find()->whereToken($token)->one()?->last_used_at);

        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token);

        self::assertResponseStatusCodeSame(200);
        self::assertNotNull(UserToken::find()->whereToken($token)->one()?->last_used_at);

        $id = UserToken::find()->whereToken($token)->select('id')->scalar();
        self::assertSame("MCP token #$id", Mcp::getComponent()->getClient());
    }

    public function testAnExpiredTokenIsNotCounted(): void
    {
        $token = Mcp::getComponent()->issueToken($this->getUserFromFixture('owner'), false, -10);

        foreach ([401, 401, 401] as $status) {
            $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token);
            self::assertResponseStatusCodeSame($status);
        }

        self::assertResponseHeaderSame('www-authenticate', 'Bearer error="invalid_token"');
    }

    public function testAForeignOriginIsForbidden(): void
    {
        $token = $this->issueToken();

        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token, ['HTTP_ORIGIN' => 'https://evil.example']);
        self::assertResponseStatusCodeSame(403);

        foreach (['https://www.test.localhost', 'https://claude.ai'] as $origin) {
            $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token, ['HTTP_ORIGIN' => $origin]);
            self::assertResponseStatusCodeSame(200);
        }
    }

    public function testGetIsNotAllowed(): void
    {
        self::$client->request('GET', 'https://www.test.localhost/mcp', server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->issueToken()]);

        self::assertResponseStatusCodeSame(405);
        self::assertResponseHeaderSame('allow', 'POST');
    }

    public function testALegacyClientIsServed(): void
    {
        $token = $this->issueToken();

        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => []]], $token);
        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame('2025-06-18', $this->getJsonResponseData()['result']['protocolVersion'] ?? null);

        $this->post(['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], $token, ['HTTP_MCP_PROTOCOL_VERSION' => '2025-06-18']);
        self::assertResponseStatusCodeSame(202);
        self::assertSame('', self::$client->getResponse()->getContent());

        $this->post(['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'], $token, ['HTTP_MCP_PROTOCOL_VERSION' => '2025-06-18']);
        self::assertContains('create_record', array_column($this->getJsonResponseData()['result']['tools'] ?? [], 'name'));
    }

    public function testAReadOnlyTokenOffersNoWriteTool(): void
    {
        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'], $this->issueToken(true));

        $names = array_column($this->getJsonResponseData()['result']['tools'] ?? [], 'name');

        self::assertContains('get_record', $names);
        self::assertNotContains('create_record', $names);
    }

    public function testTheTokenAuthenticatesItsUser(): void
    {
        $this->post([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => 'list_resources', 'arguments' => []],
        ], $this->issueToken());

        self::assertContains('redirect', array_column($this->getJsonResponseData()['result']['structuredContent']['resources'] ?? [], 'name'));
        self::assertSame($this->getUserFromFixture('owner')->id, WebUser::current()?->getId());
    }

    public function testADisabledUsersTokenIsRefused(): void
    {
        $token = Mcp::getComponent()->issueToken($this->getUserFromFixture('disabled'), false);
        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $token);

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('www-authenticate', 'Bearer error="invalid_token"');
    }

    public function testAnUnsupportedVersionHeaderIsABadRequest(): void
    {
        $this->post(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'], $this->issueToken(), ['HTTP_MCP_PROTOCOL_VERSION' => '1900-01-01']);

        self::assertResponseStatusCodeSame(400);
        self::assertSame(-32022, $this->getJsonResponseData()['error']['code'] ?? null);
    }

    public function testAModernRequestMirrorsItsHeaders(): void
    {
        $token = $this->issueToken();
        $message = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => [
                'name' => 'list_resources',
                'arguments' => [],
                '_meta' => [
                    Server::META_PROTOCOL_VERSION => Server::MODERN_PROTOCOL_VERSION,
                    Server::META_CLIENT_CAPABILITIES => [],
                ],
            ],
        ];

        $headers = [
            'HTTP_MCP_PROTOCOL_VERSION' => Server::MODERN_PROTOCOL_VERSION,
            'HTTP_MCP_METHOD' => 'tools/call',
            'HTTP_MCP_NAME' => '=?base64?' . base64_encode('list_resources') . '?=',
        ];

        $this->post($message, $token, $headers);
        self::assertResponseStatusCodeSame(200);
        self::assertSame('complete', $this->getJsonResponseData()['result']['resultType'] ?? null);

        $this->post($message, $token, [...$headers, 'HTTP_MCP_NAME' => 'get_record']);
        self::assertResponseStatusCodeSame(400);
        self::assertSame(-32020, $this->getJsonResponseData()['error']['code'] ?? null);

        $message['method'] = 'completion/complete';
        $this->post($message, $token, [...$headers, 'HTTP_MCP_METHOD' => 'completion/complete']);
        self::assertResponseStatusCodeSame(404);
        self::assertSame(-32601, $this->getJsonResponseData()['error']['code'] ?? null);
    }

    public function testWithoutHttpTheRouteIsAbsent(): void
    {
        self::assertSame('mcp/index', $this->parseRoute('mcp'));

        $this->config['components']['mcp']['enableHttp'] = false;
        $this->reloadApplication();

        self::assertNotSame('mcp/index', $this->parseRoute('mcp'));
        self::assertArrayNotHasKey('mcp', Application::current()->controllerMap);
    }

    private function parseRoute(string $pathInfo): ?string
    {
        $request = new Request(['pathInfo' => $pathInfo, 'hostInfo' => 'https://www.test.localhost']);
        $route = Application::current()->getUrlManager()->parseRequest($request);

        return is_array($route) ? $route[0] : null;
    }

    private function issueToken(bool $readOnly = false): string
    {
        return Mcp::getComponent()->issueToken($this->getUserFromFixture('owner'), $readOnly);
    }

    /**
     * @param array<string, mixed> $message
     * @param array<string, string> $server
     */
    private function post(array $message, ?string $token = null, array $server = []): void
    {
        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = "Bearer $token";
        }

        self::$client->request(
            'POST',
            'https://www.test.localhost/mcp',
            server: ['CONTENT_TYPE' => 'application/json', ...$server],
            content: json_encode($message, JSON_THROW_ON_ERROR),
        );
    }
}
