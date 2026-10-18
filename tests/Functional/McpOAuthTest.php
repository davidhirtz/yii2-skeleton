<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Mcp\OAuth\ClientMetadata;
use Hirtz\Skeleton\Mcp\OAuth\ClientMetadataFetcher;
use Hirtz\Skeleton\Mcp\OAuth\OAuthException;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\Browser;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\Application;
use Hirtz\Skeleton\Web\Request;
use Override;
use Yii;

class McpOAuthTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private const string CLIENT_ID = 'https://claude.ai/oauth/mcp-oauth-client-metadata';
    private const string REDIRECT_URI = 'https://claude.ai/api/mcp/auth_callback';
    private const string ISSUER = 'https://www.test.localhost';
    private const string RESOURCE = 'https://www.test.localhost/mcp';
    private const string VERIFIER = 'dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk';
    private const string CHALLENGE = 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM';

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->config['components']['mcp'] = [
            'enableHttp' => true,
            'enableOAuth' => true,
            'authenticationAttemptLimit' => 100,
        ];

        $this->reloadApplication();

        Yii::$container->set(ClientMetadataFetcher::class, fn (): ClientMetadataFetcher => new class () extends ClientMetadataFetcher {
            #[Override]
            public function fetch(string $clientId): ClientMetadata
            {
                if ($clientId !== McpOAuthTest::getClientId()) {
                    throw new OAuthException(OAuthException::INVALID_CLIENT, 'Unknown client.');
                }

                return new ClientMetadata($clientId, 'Claude 🤖', [McpOAuthTest::getRedirectUri(), 'http://localhost/callback']);
            }
        });
    }

    #[Override]
    protected function tearDown(): void
    {
        Yii::$container->clear(ClientMetadataFetcher::class);
        parent::tearDown();
    }

    public static function getClientId(): string
    {
        return self::CLIENT_ID;
    }

    public static function getRedirectUri(): string
    {
        return self::REDIRECT_URI;
    }

    public function testTheChallengePointsToTheMetadata(): void
    {
        $this->postMcp();

        self::assertResponseStatusCodeSame(401);
        self::assertResponseHeaderSame('www-authenticate', 'Bearer resource_metadata="https://www.test.localhost/.well-known/oauth-protected-resource/mcp", scope="mcp:write"');

        $this->postMcp('wrong');
        self::assertResponseHeaderSame('www-authenticate', 'Bearer error="invalid_token", resource_metadata="https://www.test.localhost/.well-known/oauth-protected-resource/mcp", scope="mcp:write"');
    }

    public function testTheDiscoveryDocuments(): void
    {
        foreach (['.well-known/oauth-protected-resource/mcp', '.well-known/oauth-protected-resource'] as $path) {
            $this->open($path);

            self::assertResponseIsSuccessful();
            self::assertResponseHeaderSame('access-control-allow-origin', '*');

            $data = $this->getJsonResponseData();
            self::assertSame(self::RESOURCE, $data['resource'] ?? null);
            self::assertSame([self::ISSUER], $data['authorization_servers'] ?? null);
            self::assertSame(['mcp:read', 'mcp:write'], $data['scopes_supported'] ?? null);
        }

        $this->open('.well-known/oauth-authorization-server');
        $data = $this->getJsonResponseData();

        self::assertSame(self::ISSUER, $data['issuer'] ?? null);
        self::assertSame('https://www.test.localhost/admin/grant/authorize', $data['authorization_endpoint'] ?? null);
        self::assertSame('https://www.test.localhost/oauth/token', $data['token_endpoint'] ?? null);
        self::assertSame(['S256'], $data['code_challenge_methods_supported'] ?? null);
        self::assertSame(['none'], $data['token_endpoint_auth_methods_supported'] ?? null);
        self::assertTrue($data['client_id_metadata_document_supported'] ?? null);
        self::assertTrue($data['authorization_response_iss_parameter_supported'] ?? null);
    }

    public function testWithoutOAuthTheRoutesAreAbsent(): void
    {
        self::assertSame('oauth/protected-resource', $this->parseRoute('.well-known/oauth-protected-resource/mcp'));
        self::assertSame('oauth/token', $this->parseRoute('oauth/token'));

        $this->config['components']['mcp']['enableOAuth'] = false;
        $this->reloadApplication();

        self::assertNotSame('oauth/protected-resource', $this->parseRoute('.well-known/oauth-protected-resource/mcp'));
        self::assertArrayNotHasKey('oauth', Application::current()->controllerMap);

        $this->postMcp();
        self::assertResponseHeaderSame('www-authenticate', 'Bearer');
    }

    public function testAGuestIsSentToTheLoginAndBack(): void
    {
        $this->open($this->getAuthorizeUrl());
        self::assertCurrentUrlEquals('/admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.card', 'Claude 🤖');
        self::assertSelectorTextContains('.card', 'claude.ai');
        self::assertSelectorExists('button[name="approve"]');
        self::assertSelectorNotExists('[data-alert="warning"]');
    }

    public function testTheWholeFlow(): void
    {
        $this->login();
        $this->open($this->getAuthorizeUrl());

        self::$client->followRedirects(false);
        $this->submit('button[name="approve"]');

        $query = $this->getRedirectQuery();
        self::assertSame('xyz', $query['state'] ?? null);
        self::assertSame(self::ISSUER, $query['iss'] ?? null);

        $grant = OAuthGrant::findOne(['user_id' => $this->getUserFromFixture('owner')->id, 'client_id' => self::CLIENT_ID]);
        self::assertNotNull($grant);
        self::assertSame('mcp:write', $grant->scope);
        self::assertSame('Claude 🤖', $grant->client_name);
        self::assertSame(self::RESOURCE, $grant->resource);

        $tokens = $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => (string)($query['code'] ?? ''),
            'code_verifier' => self::VERIFIER,
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'resource' => self::RESOURCE,
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('cache-control', 'no-store');
        self::assertSame('Bearer', $tokens['token_type'] ?? null);
        self::assertSame(3600, $tokens['expires_in'] ?? null);
        self::assertSame('mcp:write', $tokens['scope'] ?? null);

        $this->postMcp((string)$tokens['access_token'], ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
        self::assertResponseIsSuccessful();
        self::assertContains('create_record', array_column($this->getJsonResponseData()['result']['tools'] ?? [], 'name'));

        // the code is spent
        $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => (string)($query['code'] ?? ''),
            'code_verifier' => self::VERIFIER,
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame('invalid_grant', $this->getError());

        $refreshed = $this->postToken([
            'grant_type' => 'refresh_token',
            'refresh_token' => (string)$tokens['refresh_token'],
            'client_id' => self::CLIENT_ID,
        ]);

        self::assertResponseIsSuccessful();
        self::assertNotSame($tokens['refresh_token'], $refreshed['refresh_token'] ?? null);

        $this->postMcp((string)$refreshed['access_token']);
        self::assertResponseIsSuccessful();

        // presenting the rotated token again revokes the grant and every token it holds
        $this->postToken(['grant_type' => 'refresh_token', 'refresh_token' => (string)$tokens['refresh_token']]);
        self::assertSame('invalid_grant', $this->getError());

        self::assertNull(OAuthGrant::findOne($grant->id));
        self::assertSame(0, (int)UserToken::find()->where(['grant_id' => $grant->id])->count());

        $this->postMcp((string)$refreshed['access_token']);
        self::assertResponseStatusCodeSame(401);
    }

    public function testAReadOnlyGrantOffersNoWriteTool(): void
    {
        $this->login();
        $this->open($this->getAuthorizeUrl());

        self::$client->followRedirects(false);
        $this->submit('button[name="approve"]', ['readOnly' => '1']);

        $tokens = $this->exchange($this->getRedirectQuery());
        self::assertSame('mcp:read', $tokens['scope'] ?? null);

        $this->postMcp((string)$tokens['access_token'], ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']);
        $names = array_column($this->getJsonResponseData()['result']['tools'] ?? [], 'name');

        self::assertContains('get_record', $names);
        self::assertNotContains('create_record', $names);
    }

    public function testAReadRequestCannotBeWidened(): void
    {
        $this->login();
        $this->open($this->getAuthorizeUrl(['scope' => 'mcp:read offline_access']));

        self::assertSelectorExists('#grant-read-only[checked][disabled]');

        self::$client->followRedirects(false);
        $this->submit('button[name="approve"]');

        self::assertSame('mcp:read', $this->exchange($this->getRedirectQuery())['scope'] ?? null);
    }

    public function testDenyingRedirectsWithAccessDenied(): void
    {
        $this->login();
        $this->open($this->getAuthorizeUrl());

        self::$client->followRedirects(false);
        $this->submit('button[name="deny"]');

        $query = $this->getRedirectQuery();
        self::assertSame('access_denied', $query['error'] ?? null);
        self::assertSame(self::ISSUER, $query['iss'] ?? null);
        self::assertNull(OAuthGrant::findOne(['client_id' => self::CLIENT_ID]));
    }

    public function testAnUnknownClientOrRedirectIsShownNotRedirected(): void
    {
        $this->login();

        $this->open($this->getAuthorizeUrl(['client_id' => 'https://evil.example/client']));
        self::assertResponseStatusCodeSame(400);
        self::assertSelectorExists('[data-alert="danger"]');

        $this->open($this->getAuthorizeUrl(['redirect_uri' => 'https://evil.example/callback']));
        self::assertResponseStatusCodeSame(400);
        self::assertSelectorExists('[data-alert="danger"]');
    }

    public function testAnInvalidRequestIsRedirectedWithItsError(): void
    {
        $this->login();
        self::$client->followRedirects(false);

        foreach (['invalid_request' => ['code_challenge_method' => 'plain'], 'invalid_target' => ['resource' => 'https://other.example/mcp']] as $error => $params) {
            self::$client->request('GET', $this->getAuthorizeUrl($params));
            self::assertSame($error, $this->getRedirectQuery()['error'] ?? null);
        }
    }

    public function testALoopbackRedirectIsWarnedAbout(): void
    {
        $this->login();
        $this->open($this->getAuthorizeUrl(['redirect_uri' => 'http://localhost:4123/callback']));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('[data-alert="warning"]');

        self::$client->followRedirects(false);
        $this->submit('button[name="approve"]');

        self::assertStringStartsWith('http://localhost:4123/callback?code=', $this->getLocation());
    }

    public function testTheExchangeChecksVerifierAndRedirect(): void
    {
        $this->login();
        $this->open($this->getAuthorizeUrl());

        self::$client->followRedirects(false);
        $this->submit('button[name="approve"]');

        $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => (string)($this->getRedirectQuery()['code'] ?? ''),
            'code_verifier' => str_repeat('a', 43),
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
        ]);

        self::assertResponseStatusCodeSame(400);
        self::assertSame('invalid_grant', $this->getError());

        $this->postToken(['grant_type' => 'password']);
        self::assertSame('unsupported_grant_type', $this->getError());
    }

    public function testATokenForAnotherHostIsRefused(): void
    {
        $tokens = $this->authorize();

        self::$client->request('POST', 'https://test.localhost/mcp', server: [
            'HTTP_HOST' => 'test.localhost',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => "Bearer {$tokens['access_token']}",
        ], content: '{"jsonrpc":"2.0","id":1,"method":"ping"}');

        self::assertResponseStatusCodeSame(401);
    }

    public function testConnectedAppsAreListedAndRevoked(): void
    {
        $tokens = $this->authorize();

        $this->open('admin/grant/index');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.grid', 'Claude 🤖');
        self::assertSelectorExists('.tabs a[href="/admin/grant/index"]');

        $grant = OAuthGrant::findOne(['client_id' => self::CLIENT_ID]);
        self::assertNotNull($grant);

        $this->postRevoke($grant->id);

        self::assertAlertSame('The connection was revoked.', 'success');
        self::assertNull(OAuthGrant::findOne($grant->id));

        $this->postMcp((string)$tokens['access_token']);
        self::assertResponseStatusCodeSame(401);
    }

    public function testAManagerRevokesAnotherUsersGrant(): void
    {
        $admin = $this->getUserFromFixture('admin');

        $grant = OAuthGrant::create();
        $grant->user_id = $admin->id;
        $grant->client_id = self::CLIENT_ID;
        $grant->resource = self::RESOURCE;
        $grant->scope = OAuthGrant::SCOPE_READ;
        $grant->insert();

        $this->login();
        $this->open("admin/grant/view?user=$admin->id");

        self::assertResponseIsSuccessful();
        self::assertSelectorExists("#oauth-grant-$grant->id");
        self::assertSelectorExists(".tabs a[href=\"/admin/grant/view?user=$admin->id\"]");

        self::$client->followRedirects(false);
        $this->postRevoke($grant->id);

        self::assertResponseHeaderSame('location', "https://www.test.localhost/admin/grant/view?user=$admin->id");
        self::assertNull(OAuthGrant::findOne($grant->id));
    }

    public function testTheGrantGoesWithItsUser(): void
    {
        $this->authorize();

        $grant = OAuthGrant::findOne(['client_id' => self::CLIENT_ID]);
        self::assertNotNull($grant);
        self::assertSame(2, (int)UserToken::find()->where(['grant_id' => $grant->id])->count());

        $grant->delete();
        self::assertSame(0, (int)UserToken::find()->where(['grant_id' => $grant->id])->count());
    }

    /**
     * @return array<array-key, mixed>
     */
    private function authorize(): array
    {
        $this->login();
        $this->open($this->getAuthorizeUrl());

        self::$client->followRedirects(false);
        $this->submit('button[name="approve"]');

        $tokens = $this->exchange($this->getRedirectQuery());
        self::$client->followRedirects();

        return $tokens;
    }

    /**
     * @param array<array-key, mixed> $query
     * @return array<array-key, mixed>
     */
    private function exchange(array $query): array
    {
        return $this->postToken([
            'grant_type' => 'authorization_code',
            'code' => (string)($query['code'] ?? ''),
            'code_verifier' => self::VERIFIER,
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => (string)($query['redirect_uri'] ?? self::REDIRECT_URI),
        ]);
    }

    private function login(string $fixtureKey = 'owner'): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture($fixtureKey)->email,
            'password' => 'password',
        ]));
    }

    /**
     * @param array<string, string> $params
     */
    private function getAuthorizeUrl(array $params = []): string
    {
        return '/admin/grant/authorize?' . http_build_query([
            'response_type' => 'code',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'state' => 'xyz',
            'code_challenge' => self::CHALLENGE,
            'code_challenge_method' => 'S256',
            'scope' => 'mcp:write offline_access',
            'resource' => self::RESOURCE,
            ...$params,
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function getRedirectQuery(): array
    {
        self::assertResponseStatusCodeSame(302);

        $location = $this->getLocation();
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);

        return $query;
    }

    private function getLocation(): string
    {
        $location = self::$client->getResponse()->getHeader('location');
        self::assertIsString($location);

        return $location;
    }

    private function getError(): ?string
    {
        $error = $this->getJsonResponseData()['error'] ?? null;
        return is_string($error) ? $error : null;
    }

    /**
     * @param array<string, string> $params
     * @return array<array-key, mixed>
     */
    private function postToken(array $params): array
    {
        self::$client->request('POST', 'https://www.test.localhost/oauth/token', $params);
        return $this->getJsonResponseData();
    }

    /**
     * @param array<string, mixed> $message
     */
    private function postMcp(?string $token = null, array $message = ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']): void
    {
        $server = ['CONTENT_TYPE' => 'application/json'];

        if ($token !== null) {
            $server['HTTP_AUTHORIZATION'] = "Bearer $token";
        }

        self::$client ??= new Browser(['HTTP_HOST' => 'www.test.localhost', 'HTTPS' => 'on']);
        self::$client->request('POST', self::RESOURCE, server: $server, content: json_encode($message, JSON_THROW_ON_ERROR));
    }

    private function postRevoke(int $id): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request('POST', "https://www.test.localhost/admin/grant/revoke?id=$id", [
            $request->csrfParam => $request->getCsrfToken(),
        ]);
    }

    private function parseRoute(string $pathInfo): ?string
    {
        $request = new Request(['pathInfo' => $pathInfo, 'hostInfo' => self::ISSUER]);
        $route = Application::current()->getUrlManager()->parseRequest($request);

        return is_array($route) ? $route[0] : null;
    }
}
