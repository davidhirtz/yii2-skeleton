<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Mcp\JsonRpcException;
use Hirtz\Skeleton\Mcp\Server;
use Hirtz\Skeleton\Mcp\Tools\ToolCollection;
use Hirtz\Skeleton\Mcp\Tools\ToolInterface;
use Hirtz\Skeleton\Mcp\Tools\ToolResult;
use Hirtz\Skeleton\Test\TestCase;
use Override;
use RuntimeException;

class ServerTest extends TestCase
{
    public function testInitializeAnswersTheRequestedVersion(): void
    {
        $result = $this->request('initialize', ['protocolVersion' => '2025-06-18', 'capabilities' => []]);

        self::assertSame('2025-06-18', $result['protocolVersion'] ?? null);
        self::assertSame(['tools' => ['listChanged' => false]], $result['capabilities'] ?? null);
        self::assertSame(['name' => 'Tëst Ünïcode 測試', 'version' => '1.0'], $result['serverInfo'] ?? null);
    }

    public function testInitializeFallsBackToTheNewestLegacyVersion(): void
    {
        foreach (['2024-11-05', Server::MODERN_PROTOCOL_VERSION, null] as $version) {
            $result = $this->request('initialize', ['protocolVersion' => $version]);
            self::assertSame(Server::LEGACY_PROTOCOL_VERSIONS[0], $result['protocolVersion'] ?? null);
        }
    }

    public function testAModernRequestIsAnsweredWithResultTypeAndServerInfo(): void
    {
        $result = $this->request('server/discover', ['_meta' => $this->getModernMeta()]);

        self::assertSame('complete', $result['resultType'] ?? null);
        self::assertSame(Server::getSupportedVersions(), $result['supportedVersions'] ?? null);
        self::assertSame('1.0', $result['_meta'][Server::META_SERVER_INFO]['version'] ?? null);

        $result = $this->request('tools/list', ['_meta' => $this->getModernMeta()]);
        self::assertSame('complete', $result['resultType'] ?? null);
        self::assertSame(0, $result['ttlMs'] ?? null);
        self::assertSame('private', $result['cacheScope'] ?? null);

        $result = $this->request('ping', ['_meta' => $this->getModernMeta()]);
        self::assertArrayNotHasKey('ttlMs', $result);
    }

    public function testAModernRequestWithoutCapabilitiesIsInvalid(): void
    {
        $response = $this->createServer()->handle($this->createMessage('ping', [
            '_meta' => [Server::META_PROTOCOL_VERSION => Server::MODERN_PROTOCOL_VERSION],
        ]));

        self::assertSame(JsonRpcException::INVALID_PARAMS, $response['error']['code'] ?? null);
    }

    public function testAnUnsupportedVersionListsTheSupportedOnes(): void
    {
        $response = $this->createServer()->handle($this->createMessage('ping', [
            '_meta' => [Server::META_PROTOCOL_VERSION => '1900-01-01'],
        ]));

        self::assertSame(7, $response['id'] ?? null);
        self::assertSame([
            'code' => JsonRpcException::UNSUPPORTED_PROTOCOL_VERSION,
            'message' => 'Unsupported protocol version',
            'data' => [
                'supported' => Server::getSupportedVersions(),
                'requested' => '1900-01-01',
            ],
        ], $response['error'] ?? null);
    }

    public function testALegacyRequestHasNoResultType(): void
    {
        self::assertSame([], (array)$this->request('ping'));
        self::assertArrayNotHasKey('resultType', $this->request('tools/list'));
    }

    public function testAnUnknownMethodIsNotFound(): void
    {
        $response = $this->createServer()->handle($this->createMessage('resources/list'));
        self::assertSame(JsonRpcException::METHOD_NOT_FOUND, $response['error']['code'] ?? null);
    }

    public function testABatchIsRefused(): void
    {
        $json = $this->createServer()->handleJson('[{"jsonrpc":"2.0","id":1,"method":"ping"}]');
        self::assertSame('{"jsonrpc":"2.0","id":null,"error":{"code":-32600,"message":"Invalid request: batches are not supported"}}', $json);
    }

    public function testANotificationIsNotAnswered(): void
    {
        $server = $this->createServer();

        self::assertNull($server->handle(['jsonrpc' => '2.0', 'method' => 'notifications/initialized']));
        self::assertNull($server->handleJson('{"jsonrpc":"2.0","method":"notifications/cancelled","params":{"requestId":1}}'));
    }

    public function testMalformedJsonIsAParseError(): void
    {
        $json = $this->createServer()->handleJson('{"jsonrpc":"2.0",');
        self::assertSame('{"jsonrpc":"2.0","id":null,"error":{"code":-32700,"message":"Parse error"}}', $json);
    }

    public function testAnInvalidRequestIsRefused(): void
    {
        $server = $this->createServer();

        foreach ([
            ['jsonrpc' => '1.0', 'id' => 1, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'id' => 1],
            ['jsonrpc' => '2.0', 'id' => null, 'method' => 'ping'],
            ['jsonrpc' => '2.0', 'id' => 1.5, 'method' => 'ping'],
        ] as $message) {
            self::assertSame(JsonRpcException::INVALID_REQUEST, $server->handle($message)['error']['code'] ?? null);
        }

        $response = $server->handle(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping', 'params' => [1, 2]]);
        self::assertSame(JsonRpcException::INVALID_PARAMS, $response['error']['code'] ?? null);
    }

    public function testToolsAreListedWithAnnotations(): void
    {
        $tools = $this->request('tools/list')['tools'] ?? [];

        self::assertSame(['echo', 'write'], array_column($tools, 'name'));
        self::assertSame([
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ], $tools[0]['annotations'] ?? null);
    }

    public function testAReadOnlyCollectionHidesWriteTools(): void
    {
        $server = $this->createServer(readOnly: true);
        $response = $server->handle($this->createMessage('tools/list'));

        self::assertSame(['echo'], array_column($response['result']['tools'] ?? [], 'name'));

        $response = $server->handle($this->createMessage('tools/call', ['name' => 'write', 'arguments' => []]));
        self::assertSame(JsonRpcException::INVALID_PARAMS, $response['error']['code'] ?? null);
    }

    public function testAToolCallAnswersStructuredAndTextContent(): void
    {
        $result = $this->request('tools/call', ['name' => 'echo', 'arguments' => ['text' => 'Åsa ąb Привет 東京 🎉 Mu' . "\u{308}" . 'ller']]);

        self::assertFalse($result['isError'] ?? null);
        self::assertSame(['text' => 'Åsa ąb Привет 東京 🎉 Mu' . "\u{308}" . 'ller'], $result['structuredContent'] ?? null);
        self::assertSame('{"text":"Åsa ąb Привет 東京 🎉 Mu' . "\u{308}" . 'ller"}', $result['content'][0]['text'] ?? null);
    }

    public function testInvalidArgumentsAreAToolError(): void
    {
        $result = $this->request('tools/call', ['name' => 'echo', 'arguments' => ['count' => 'many']]);

        self::assertTrue($result['isError'] ?? null);
        self::assertSame([
            'text' => 'Missing required argument "text".',
            'count' => 'Argument "count" must be of type integer.',
        ], $result['structuredContent']['errors'] ?? null);
    }

    public function testAFailingToolHidesTheException(): void
    {
        $result = $this->request('tools/call', ['name' => 'echo', 'arguments' => ['text' => 'fail']]);

        self::assertTrue($result['isError'] ?? null);
        self::assertSame(['error' => 'The tool failed with an unexpected error.'], $result['structuredContent'] ?? null);
        self::assertStringNotContainsString('secret', json_encode($result, JSON_THROW_ON_ERROR));
    }

    public function testAnUnknownToolIsInvalidParams(): void
    {
        $response = $this->createServer()->handle($this->createMessage('tools/call', ['name' => 'nope']));

        self::assertSame(JsonRpcException::INVALID_PARAMS, $response['error']['code'] ?? null);
        self::assertSame('Unknown tool: nope', $response['error']['message'] ?? null);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function request(string $method, array $params = []): array
    {
        $response = $this->createServer()->handle($this->createMessage($method, $params));
        self::assertArrayNotHasKey('error', $response ?? []);

        /** @var array<string, mixed> */
        return (array)($response['result'] ?? self::fail('No result'));
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function createMessage(string $method, array $params = []): array
    {
        return ['jsonrpc' => '2.0', 'id' => 7, 'method' => $method, 'params' => $params];
    }

    /**
     * @return array<string, mixed>
     */
    private function getModernMeta(): array
    {
        return [
            Server::META_PROTOCOL_VERSION => Server::MODERN_PROTOCOL_VERSION,
            Server::META_CLIENT_CAPABILITIES => [],
        ];
    }

    private function createServer(bool $readOnly = false): Server
    {
        return new Server(new ToolCollection([new EchoTool(), new WriteTool()], $readOnly), 'Tëst Ünïcode 測試', '1.0');
    }
}

class EchoTool implements ToolInterface
{
    #[Override]
    public function getName(): string
    {
        return 'echo';
    }

    #[Override]
    public function getDescription(): string
    {
        return 'Echoes the text';
    }

    #[Override]
    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'text' => ['type' => 'string'],
                'count' => ['type' => 'integer'],
            ],
            'required' => ['text'],
        ];
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return true;
    }

    #[Override]
    public function isIdempotent(): bool
    {
        return true;
    }

    #[Override]
    public function call(array $arguments): ToolResult
    {
        if ($arguments['text'] === 'fail') {
            throw new RuntimeException('secret');
        }

        return ToolResult::success($arguments);
    }
}

class WriteTool extends EchoTool
{
    #[Override]
    public function getName(): string
    {
        return 'write';
    }

    #[Override]
    public function isReadOnly(): bool
    {
        return false;
    }
}
