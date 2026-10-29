<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\OAuthGrant;
use Hirtz\Skeleton\Models\UserToken;
use Yii;

/**
 * Presenting a refresh token already rotated away revokes the whole grant (OAuth 2.1 §4.3.1), since either the
 * client or a thief holds a stale copy.
 */
class TokenIssuer
{
    public function __construct(private readonly Mcp $mcp)
    {
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<string, int|string>
     * @throws OAuthException
     */
    public function handle(array $params, string $resource): array
    {
        return match ($params['grant_type'] ?? null) {
            'authorization_code' => $this->exchangeCode($params, $resource),
            'refresh_token' => $this->refresh($params, $resource),
            default => throw new OAuthException(OAuthException::UNSUPPORTED_GRANT_TYPE, 'Only authorization_code and refresh_token are supported.'),
        };
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<string, int|string>
     * @throws OAuthException
     */
    protected function exchangeCode(array $params, string $resource): array
    {
        $code = $this->getString($params, 'code');
        $authorization = $code !== null ? AuthorizationCode::consume($code) : null;

        if ($authorization === null) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The code is invalid, expired or spent.');
        }

        if ($this->getString($params, 'client_id') !== $authorization->clientId) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The code was issued to another client.');
        }

        if ($this->getString($params, 'redirect_uri') !== $authorization->redirectUri) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The redirect_uri does not match.');
        }

        if (!$authorization->verifyCodeVerifier($this->getString($params, 'code_verifier') ?? '')) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The code_verifier does not match.');
        }

        $this->validateResource($params, $authorization->resource);

        $grant = $this->findGrant($authorization->grantId);

        if ($grant === null || !ResourceUri::equals($grant->resource, $resource)) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The grant was revoked.');
        }

        return $this->issueTokens($grant);
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<string, int|string>
     * @throws OAuthException
     */
    protected function refresh(array $params, string $resource): array
    {
        $token = $this->getString($params, 'refresh_token');

        $record = $token === null ? null : UserToken::find()
            ->whereToken($token)
            ->andWhere(['type' => [UserToken::TYPE_MCP_REFRESH, UserToken::TYPE_MCP_ROTATED]])
            ->andWhere(['not', ['grant_id' => null]])
            ->unexpired()
            ->one();

        if ($record === null || $record->grant_id === null) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The refresh token is invalid or expired.');
        }

        $grant = $this->findGrant($record->grant_id);

        if ($grant === null) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The grant was revoked.');
        }

        if ($record->type === UserToken::TYPE_MCP_ROTATED) {
            $this->revokeReusedGrant($grant);
        }

        $clientId = $this->getString($params, 'client_id');

        if ($clientId !== null && $clientId !== $grant->client_id) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The refresh token was issued to another client.');
        }

        $this->validateResource($params, $grant->resource);

        if (!ResourceUri::equals($grant->resource, $resource)) {
            throw new OAuthException(OAuthException::INVALID_GRANT, 'The refresh token was issued for another resource.');
        }

        if (!$this->rotate($record)) {
            $this->revokeReusedGrant($grant);
        }

        return $this->issueTokens($grant);
    }

    /**
     * Conditional, so of two requests presenting the same token only one wins and the other counts as a reuse.
     */
    protected function rotate(UserToken $record): bool
    {
        return UserToken::updateAll(['type' => UserToken::TYPE_MCP_ROTATED], [
            'id' => $record->id,
            'type' => UserToken::TYPE_MCP_REFRESH,
        ]) === 1;
    }

    /**
     * @throws OAuthException
     */
    protected function revokeReusedGrant(OAuthGrant $grant): never
    {
        Yii::warning("A rotated refresh token of OAuth grant $grant->id was presented, the grant is revoked", __METHOD__);
        $grant->delete();

        throw new OAuthException(OAuthException::INVALID_GRANT, 'The refresh token was already used.');
    }

    /**
     * @param array<array-key, mixed> $params
     * @throws OAuthException
     */
    protected function validateResource(array $params, string $resource): void
    {
        $requested = $this->getString($params, 'resource');

        if ($requested !== null && !ResourceUri::equals($requested, $resource)) {
            throw new OAuthException(OAuthException::INVALID_TARGET, 'The resource does not match the grant.');
        }
    }

    /**
     * A user who could not log in is granted nothing, as {@see Mcp::findToken()} refuses their tokens.
     */
    protected function findGrant(int $id): ?OAuthGrant
    {
        $grant = OAuthGrant::find()
            ->where(['id' => $id])
            ->with('user')
            ->one();

        $user = $grant?->user;

        return $user === null || ($user->isDisabled() && !$user->isOwner()) ? null : $grant;
    }

    /**
     * @return array<string, int|string>
     */
    protected function issueTokens(OAuthGrant $grant): array
    {
        UserToken::deleteAll([
            'and',
            ['grant_id' => $grant->id],
            ['<=', 'expires_at', gmdate('Y-m-d H:i:s')],
        ]);

        $security = Yii::$app->getSecurity();
        $accessToken = $security->generateRandomString($this->mcp->tokenLength);
        $refreshToken = $security->generateRandomString($this->mcp->tokenLength);

        $type = $grant->isReadOnly() ? UserToken::TYPE_MCP_READ : UserToken::TYPE_MCP_WRITE;
        UserToken::issue($grant->user_id, $type, $accessToken, $this->mcp->accessTokenLifetime, $grant->id);
        UserToken::issue($grant->user_id, UserToken::TYPE_MCP_REFRESH, $refreshToken, $this->mcp->refreshTokenLifetime, $grant->id);

        $grant->touchLastUsedAt(0);

        return [
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->mcp->accessTokenLifetime,
            'refresh_token' => $refreshToken,
            'scope' => $grant->scope,
        ];
    }

    /**
     * @param array<array-key, mixed> $params
     */
    private function getString(array $params, string $name): ?string
    {
        $value = $params[$name] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }
}
