<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\Cookie;

class UserIdentityCookieTest extends TestCase
{
    use UserFixtureTrait;

    private string $identityCookieValue;

    public function testValidCookieIsRenewed(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->setIdentityCookie($user->getId(), (string)$user->getAuthKey());
        $this->login($user);

        $cookie = $this->getResponseIdentityCookie();

        self::assertNotNull($cookie);
        self::assertSame($this->identityCookieValue, $cookie->value);
        self::assertGreaterThan(time(), $cookie->expire);
    }

    public function testStaleCookieIsRemoved(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->setIdentityCookie($user->getId(), 'this-is-not-the-current-auth-key');
        $this->login($user);

        $this->assertIdentityCookieRemoved();
    }

    public function testCookieForAnotherUserIsRemoved(): void
    {
        $user = $this->getUserFromFixture('owner');
        $other = $this->getUserFromFixture('admin');

        $this->setIdentityCookie($other->getId(), (string)$other->getAuthKey());
        $this->login($user);

        $this->assertIdentityCookieRemoved();
    }

    public function testMalformedCookieIsRemoved(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->setIdentityCookie($user->getId(), (string)$user->getAuthKey(), '"not-an-array"');
        $this->login($user);

        $this->assertIdentityCookieRemoved();
    }

    public function testNoCookieSendsNothing(): void
    {
        $this->login($this->getUserFromFixture('owner'));

        self::assertNull($this->getResponseIdentityCookie());
    }

    /**
     * Drives the renewal the way a request does — `renewAuthStatus()` finds the identity in the session and then
     * renews the cookie — rather than through `login()`, which sends a cookie of its own.
     */
    private function login(User $user): void
    {
        $webuser = Yii::$app->getUser();
        $session = Yii::$app->getSession();
        $session->open();

        $session->set($webuser->idParam, $user->getId());
        $session->set($webuser->authKeyParam, $user->getAuthKey());

        self::assertNotNull($webuser->getIdentity());
    }

    private function setIdentityCookie(int|string $id, string $authKey, ?string $value = null): void
    {
        $this->identityCookieValue = $value ?? (string)json_encode([$id, $authKey, 3600]);
        $value = $this->identityCookieValue;
        $request = Yii::$app->getRequest();

        $_COOKIE['_identity'] = $request->enableCookieValidation
            ? Yii::$app->getSecurity()->hashData(
                serialize(['_identity', $value]),
                $request->cookieValidationKey
            )
            : $value;
    }

    private function getResponseIdentityCookie(): ?Cookie
    {
        return Yii::$app->getResponse()->getCookies()->get('_identity');
    }

    private function assertIdentityCookieRemoved(): void
    {
        $cookie = $this->getResponseIdentityCookie();

        self::assertNotNull($cookie);
        self::assertSame('', $cookie->value);
        self::assertSame(1, $cookie->expire);
    }
}
