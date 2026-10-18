<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp;

use Hirtz\Skeleton\Mcp\JsonRpcException;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\Prompt;
use Hirtz\Skeleton\Mcp\RecordResources;
use Hirtz\Skeleton\Mcp\Resource;
use Hirtz\Skeleton\Mcp\Server;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class RecordResourcesTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheResourcesAreTemplatedListedAndRead(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));
        $redirect = $this->createRedirect();
        $server = Mcp::getComponent()->createServer();

        $templates = $this->getResult($server, 'resources/templates/list')['resourceTemplates'] ?? [];
        self::assertContains('record://redirect/{id}', array_column($templates, 'uriTemplate'));

        $uri = "record://redirect/$redirect->id";
        $resources = $this->getResult($server, 'resources/list')['resources'] ?? [];
        self::assertContains($uri, array_column($resources, 'uri'));

        $contents = $this->getResult($server, 'resources/read', ['uri' => $uri])['contents'] ?? [];
        self::assertSame($uri, $contents[0]['uri'] ?? null);
        self::assertSame('application/json', $contents[0]['mimeType'] ?? null);

        $data = json_decode((string)($contents[0]['text'] ?? ''), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('alt/åsa-東京-😀', $data['attributes']['request_uri'] ?? null);
    }

    public function testAnUnknownOrForbiddenRecordIsNotFound(): void
    {
        $redirect = $this->createRedirect();
        $server = Mcp::getComponent()->createServer();

        foreach (['record://redirect/0', 'record://nothing/1', 'https://example.com/', "record://redirect/$redirect->id"] as $uri) {
            $response = $server->handle($this->createMessage('resources/read', ['uri' => $uri]));
            self::assertSame(JsonRpcException::INVALID_PARAMS, $response['error']['code'] ?? null, $uri);
        }

        // a guest reaches no resource at all
        self::assertSame([], $this->getResult($server, 'resources/templates/list')['resourceTemplates'] ?? null);
    }

    public function testTheCapabilitiesNameWhatIsOffered(): void
    {
        // A sibling bundle may register prompts, as the cms does in the monorepo
        Mcp::getComponent()->prompts = [];
        $capabilities = $this->getResult(Mcp::getComponent()->createServer(), 'initialize')['capabilities'] ?? [];

        self::assertArrayHasKey('resources', $capabilities);
        self::assertArrayNotHasKey('prompts', $capabilities);

        $this->config['components']['mcp']['enableResources'] = false;
        $this->reloadApplication();

        $server = Mcp::getComponent()->createServer();
        self::assertArrayNotHasKey('resources', $this->getResult($server, 'initialize')['capabilities'] ?? []);
        self::assertSame(JsonRpcException::METHOD_NOT_FOUND, $server->handle($this->createMessage('resources/list'))['error']['code'] ?? null);
    }

    public function testARecordsImageIsShownBesideItsJson(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));
        $redirect = $this->createRedirect();

        Mcp::getComponent()->resources = [
            fn (): Resource => Resource::make('redirect', Redirect::class)
                ->image(fn (Redirect $redirect): array => ['data' => base64_encode("Bild $redirect->id"), 'mimeType' => 'image/png']),
        ];

        $server = Mcp::getComponent()->createServer();

        $contents = $this->getResult($server, 'resources/read', ['uri' => "record://redirect/$redirect->id"])['contents'] ?? [];
        self::assertSame(['uri' => "record://redirect/$redirect->id", 'mimeType' => 'image/png', 'blob' => base64_encode("Bild $redirect->id")], $contents[1] ?? null);

        $content = $this->getResult($server, 'tools/call', ['name' => 'get_record', 'arguments' => ['resource' => 'redirect', 'id' => $redirect->id]])['content'] ?? [];
        self::assertSame(['type' => 'image', 'data' => base64_encode("Bild $redirect->id"), 'mimeType' => 'image/png'], $content[1] ?? null);
    }

    public function testAModernReadCarriesCachingHints(): void
    {
        $this->getWebUser()->setIdentity($this->getUserFromFixture('owner'));
        $redirect = $this->createRedirect();

        $result = $this->getResult(Mcp::getComponent()->createServer(), 'resources/read', [
            'uri' => "record://redirect/$redirect->id",
            '_meta' => [
                Server::META_PROTOCOL_VERSION => Server::MODERN_PROTOCOL_VERSION,
                Server::META_CLIENT_CAPABILITIES => [],
            ],
        ]);

        self::assertSame('complete', $result['resultType'] ?? null);
        self::assertSame(0, $result['ttlMs'] ?? null);
        self::assertSame('private', $result['cacheScope'] ?? null);
    }

    public function testPromptsAreListedAndFilledIn(): void
    {
        // Assigned, not configured: the config would append to a sibling bundle's prompts
        Mcp::getComponent()->prompts = [
            fn (): Prompt => Prompt::make('new_post')
                ->title('Neuer Beitrag')
                ->description('Drafts a post')
                ->argument('topic', 'What it is about', true)
                ->argument('tone')
                ->text(fn (array $arguments): string => "Draft a post about {$arguments['topic']}" . (isset($arguments['tone']) ? ", {$arguments['tone']}" : '') . '.'),
        ];

        $server = Mcp::getComponent()->createServer();

        self::assertArrayHasKey('prompts', $this->getResult($server, 'initialize')['capabilities'] ?? []);

        $prompts = $this->getResult($server, 'prompts/list')['prompts'] ?? [];
        self::assertSame([
            'name' => 'new_post',
            'title' => 'Neuer Beitrag',
            'description' => 'Drafts a post',
            'arguments' => [
                ['name' => 'topic', 'description' => 'What it is about', 'required' => true],
                ['name' => 'tone'],
            ],
        ], $prompts[0] ?? null);

        $result = $this->getResult($server, 'prompts/get', ['name' => 'new_post', 'arguments' => ['topic' => 'Ærø 東京']]);
        self::assertSame('Draft a post about Ærø 東京.', $result['messages'][0]['content']['text'] ?? null);
        self::assertSame('user', $result['messages'][0]['role'] ?? null);

        foreach ([['name' => 'new_post'], ['name' => 'unknown']] as $params) {
            self::assertSame(JsonRpcException::INVALID_PARAMS, $server->handle($this->createMessage('prompts/get', $params))['error']['code'] ?? null);
        }
    }

    public function testAUriRoundTrips(): void
    {
        self::assertSame(['entry', '8'], RecordResources::parseUri(RecordResources::getUri('entry', 8)));
        self::assertNull(RecordResources::parseUri('record://entry/8/sections'));
        self::assertNull(RecordResources::parseUri('record://entry'));
    }

    private function createRedirect(): Redirect
    {
        $redirect = Redirect::create();
        $redirect->type = Redirect::TYPE_FOUND;
        $redirect->request_uri = 'alt/åsa-東京-😀';
        $redirect->url = 'neu';
        $redirect->insert();

        return $redirect;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<array-key, mixed>
     */
    private function getResult(Server $server, string $method, array $params = []): array
    {
        $response = $server->handle($this->createMessage($method, $params));
        self::assertIsArray($response['result'] ?? null, json_encode($response, JSON_UNESCAPED_UNICODE) ?: '');

        return (array)$response['result'];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function createMessage(string $method, array $params = []): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params];
    }
}
