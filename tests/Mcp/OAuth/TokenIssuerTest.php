<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Mcp\OAuth;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Mcp\OAuth\AuthorizationCode;
use Hirtz\Skeleton\Mcp\OAuth\OAuthException;
use Hirtz\Skeleton\Mcp\OAuth\TokenIssuer;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class TokenIssuerTest extends TestCase
{
    use UserFixtureTrait;

    private const string RESOURCE = 'https://www.example.com/mcp';

    public function testARefreshRotatesAndKeepsTheScope(): void
    {
        $grant = $this->createGrant('owner', OAuthGrant::SCOPE_READ);
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        $tokens = $this->refresh('refresh-token');

        self::assertSame(OAuthGrant::SCOPE_READ, $tokens['scope']);
        self::assertSame(UserToken::TYPE_MCP_ROTATED, UserToken::find()->whereToken('refresh-token')->one()?->type);
        self::assertSame(UserToken::TYPE_MCP_READ, UserToken::find()->whereToken((string)$tokens['access_token'])->one()?->type);
        self::assertNotNull(OAuthGrant::findOne($grant->id)?->last_used_at);
    }

    public function testARotatedRefreshTokenRevokesTheGrant(): void
    {
        $grant = $this->createGrant('owner');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        $this->refresh('refresh-token');
        $this->assertInvalidGrant('refresh-token');

        self::assertNull(OAuthGrant::findOne($grant->id));
    }

    public function testARefreshLosingTheRaceRevokesTheGrant(): void
    {
        $grant = $this->createGrant('owner');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        try {
            (new RacedTokenIssuer(Mcp::getComponent()))->handle([
                'grant_type' => 'refresh_token',
                'refresh_token' => 'refresh-token',
            ], self::RESOURCE);

            self::fail('Both requests were issued tokens.');
        } catch (OAuthException $exception) {
            self::assertSame(OAuthException::INVALID_GRANT, $exception->error);
        }

        self::assertNull(OAuthGrant::findOne($grant->id));
    }

    public function testACodeIsExchangedOnce(): void
    {
        $code = (new AuthorizationCode(1, 'https://claude.ai/client', 'https://claude.ai/callback', 'challenge', self::RESOURCE))->issue();

        self::assertNotNull(AuthorizationCode::consume($code));
        self::assertNull(AuthorizationCode::consume($code));
    }

    public function testAnExpiredRefreshTokenIsAnInvalidGrant(): void
    {
        $grant = $this->createGrant('owner');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', -10, $grant->id);

        $this->assertInvalidGrant('refresh-token');
    }

    public function testAnAccessTokenIsNoRefreshToken(): void
    {
        $grant = $this->createGrant('owner');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_WRITE, 'access-token', 60, $grant->id);

        $this->assertInvalidGrant('access-token');
    }

    public function testADisabledUserIsGrantedNothing(): void
    {
        $grant = $this->createGrant('disabled');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        $this->assertInvalidGrant('refresh-token');
    }

    public function testAnotherResourceIsAnInvalidTarget(): void
    {
        $grant = $this->createGrant('owner');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        try {
            $this->refresh('refresh-token', ['resource' => 'https://other.example/mcp']);
            self::fail('A foreign resource was accepted.');
        } catch (OAuthException $exception) {
            self::assertSame(OAuthException::INVALID_TARGET, $exception->error);
        }
    }

    public function testExpiredTokensOfTheGrantAreCleanedUp(): void
    {
        $grant = $this->createGrant('owner');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_WRITE, 'expired', -10, $grant->id);
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        $this->refresh('refresh-token');

        self::assertNull(UserToken::find()->whereToken('expired')->one());
        self::assertSame(3, (int)UserToken::find()->where(['grant_id' => $grant->id])->count());
    }

    public function testTheGrantGoesWithItsUser(): void
    {
        $grant = $this->createGrant('disabled');
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);

        $this->getUserFromFixture('disabled')->delete();

        self::assertNull(OAuthGrant::findOne($grant->id));
        self::assertNull(UserToken::find()->whereToken('refresh-token')->one());
    }

    private function createGrant(string $fixtureKey, string $scope = OAuthGrant::SCOPE_WRITE): OAuthGrant
    {
        $grant = OAuthGrant::create();
        $grant->user_id = $this->getUserFromFixture($fixtureKey)->id;
        $grant->client_id = 'https://claude.ai/oauth/mcp-oauth-client-metadata';
        $grant->resource = self::RESOURCE;
        $grant->scope = $scope;
        $grant->insert();

        return $grant;
    }

    /**
     * @param array<string, string> $params
     * @return array<string, int|string>
     */
    private function refresh(string $token, array $params = []): array
    {
        return (new TokenIssuer(Mcp::getComponent()))->handle([
            'grant_type' => 'refresh_token',
            'refresh_token' => $token,
            ...$params,
        ], self::RESOURCE);
    }

    private function assertInvalidGrant(string $token): void
    {
        try {
            $this->refresh($token);
            self::fail('The refresh token was accepted.');
        } catch (OAuthException $exception) {
            self::assertSame(OAuthException::INVALID_GRANT, $exception->error);
        }
    }
}

/**
 * A concurrent request rotates the token between this one reading and rotating it.
 */
class RacedTokenIssuer extends TokenIssuer
{
    #[Override]
    protected function rotate(UserToken $record): bool
    {
        parent::rotate($record);
        return parent::rotate($record);
    }
}
