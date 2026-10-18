<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Mcp\OAuth;

use Hirtz\Skeleton\Models\UserToken;
use Yii;

/**
 * Kept in the cache under the code's HMAC: a code lives a minute and is spent on its first exchange, so nothing is
 * left to clean up, and a flushed cache only means approving again.
 */
readonly class AuthorizationCode
{
    public function __construct(
        public int $grantId,
        public string $clientId,
        public string $redirectUri,
        public string $codeChallenge,
        public string $resource,
    ) {
    }

    public function issue(int $lifetime = 60): string
    {
        $code = Yii::$app->getSecurity()->generateRandomString(48);
        Yii::$app->getCache()?->set(self::getCacheKey($code), $this, $lifetime);

        return $code;
    }

    public static function consume(string $code): ?self
    {
        $cache = Yii::$app->getCache();
        $key = self::getCacheKey($code);
        $value = $cache?->get($key);

        // Only one of two concurrent exchanges deletes the entry (`DbCache` answers `true` regardless)
        if (!$value instanceof self || !$cache->delete($key)) {
            return null;
        }

        return $value;
    }

    public function verifyCodeVerifier(string $verifier): bool
    {
        if (!preg_match('/^[A-Za-z0-9._~-]{43,128}$/', $verifier)) {
            return false;
        }

        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return hash_equals($this->codeChallenge, $challenge);
    }

    /**
     * @return array{class-string, string}
     */
    private static function getCacheKey(string $code): array
    {
        return [self::class, UserToken::hash($code)];
    }
}
