<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Web;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\Cookie;

/**
 * A browser an account logged in from keeps its own attempt count, so strangers guessing the password cannot lock
 * the account's owner out (monorepo issue #461).
 */
class UserDeviceCookieTest extends TestCase
{
    use UserFixtureTrait;

    private const string STRANGER = '192.0.2.1';
    private const string OWNER = '198.51.100.1';

    public function testALoginIssuesADeviceCookie(): void
    {
        $user = $this->getUserFromFixture('owner');
        $this->getWebUser()->login($user);

        $cookie = $this->getResponseDeviceCookie();

        self::assertNotNull($cookie);
        self::assertTrue($cookie->httpOnly);
        self::assertSame(Cookie::SAME_SITE_LAX, $cookie->sameSite);
        self::assertSame($this->getWebUser()->identityCookie['secure'], $cookie->secure);
        self::assertGreaterThan(time() + 86400, $cookie->expire);

        $data = json_decode($cookie->value, true);

        self::assertIsArray($data);
        self::assertSame($user->id, $data[0]);
        self::assertStringNotContainsString((string)$user->auth_key, $cookie->value);
    }

    public function testStrangersCannotLockTheOwnerOutOfAKnownBrowser(): void
    {
        $webuser = $this->getWebUser();
        $webuser->loginAttemptLimit = 3;

        $email = $this->getUserFixtureData('owner')['email'];
        $device = $this->createDeviceCookie('owner');

        $webuser->ipAddress = self::STRANGER;

        for ($i = 0; $i < 3; $i++) {
            self::assertFalse($this->createForm($email, 'wrong')->login());
        }

        self::assertTrue($webuser->isLoginAttemptLimitReached($email));

        $webuser->ipAddress = self::OWNER;
        $this->setRequestDeviceCookie($device);

        self::assertFalse($webuser->isLoginAttemptLimitReached($email));
        self::assertTrue($this->createForm($email, 'password')->login());
    }

    /**
     * The known browser's failures are its own: the account stays open to the owner's other browsers, and a stolen
     * cookie is worth no more guesses than the account allows.
     */
    public function testAKnownBrowserLocksOnlyItself(): void
    {
        $webuser = $this->getWebUser();
        $webuser->loginAttemptLimit = 3;
        $webuser->ipAddress = self::OWNER;

        $email = $this->getUserFixtureData('owner')['email'];
        $this->setRequestDeviceCookie($this->createDeviceCookie('owner'));

        for ($i = 0; $i < 3; $i++) {
            self::assertFalse($this->createForm($email, 'wrong')->login());
        }

        self::assertTrue($webuser->isLoginAttemptLimitReached($email));

        $form = $this->createForm($email, 'password');

        self::assertFalse($form->login());
        self::assertSame(Yii::t('skeleton', 'LOGIN_TOO_MANY_ATTEMPTS'), $form->getFirstError('email'));

        $this->removeRequestDeviceCookie();
        $webuser->ipAddress = self::STRANGER;

        self::assertFalse($webuser->isLoginAttemptLimitReached($email));
    }

    public function testAnotherAccountsCookieCountsNothing(): void
    {
        $webuser = $this->getWebUser();
        $webuser->loginAttemptLimit = 3;
        $webuser->ipAddress = self::STRANGER;

        $email = $this->getUserFixtureData('owner')['email'];

        for ($i = 0; $i < 3; $i++) {
            self::assertFalse($this->createForm($email, 'wrong')->login());
        }

        $webuser->ipAddress = self::OWNER;
        $this->setRequestDeviceCookie($this->createDeviceCookie('admin'));

        self::assertTrue($webuser->isLoginAttemptLimitReached($email));
    }

    public function testANewAuthKeyInvalidatesEveryDeviceCookie(): void
    {
        $webuser = $this->getWebUser();
        $webuser->loginAttemptLimit = 1;
        $webuser->ipAddress = self::STRANGER;

        $email = $this->getUserFixtureData('owner')['email'];
        $device = $this->createDeviceCookie('owner');

        self::assertFalse($this->createForm($email, 'wrong')->login());

        $user = $this->getUserFromFixture('owner');
        $user->generateAuthKey();
        $user->updateAttributes(['auth_key' => $user->auth_key]);

        $webuser->ipAddress = self::OWNER;
        $this->setRequestDeviceCookie($device);

        self::assertTrue($webuser->isLoginAttemptLimitReached($email));
    }

    public function testAForgedCookieCountsNothing(): void
    {
        $webuser = $this->getWebUser();
        $webuser->loginAttemptLimit = 1;
        $webuser->ipAddress = self::STRANGER;

        $user = $this->getUserFromFixture('owner');
        self::assertFalse($this->createForm($user->email, 'wrong')->login());

        $webuser->ipAddress = self::OWNER;

        foreach ([json_encode([$user->id, 'nonce', str_repeat('0', 64)]), '"Müller 🔑"', 'not json'] as $value) {
            $this->setRequestDeviceCookie((string)$value);
            self::assertTrue($webuser->isLoginAttemptLimitReached($user->email));
        }
    }

    public function testDeviceCookiesCanBeTurnedOff(): void
    {
        $webuser = $this->getWebUser();
        $webuser->enableDeviceCookies = false;
        $webuser->login($this->getUserFromFixture('owner'));

        self::assertNull($this->getResponseDeviceCookie());
    }

    private function createDeviceCookie(string $alias): string
    {
        $this->getWebUser()->sendDeviceCookie($this->getUserFromFixture($alias));

        $value = $this->getResponseDeviceCookie()?->value;
        $this->getWebResponse()->getCookies()->removeAll();

        return is_string($value) ? $value : self::fail('No device cookie was sent.');
    }

    /**
     * Straight into the parsed collection: the request has read its cookies by the time a test changes browsers.
     */
    private function setRequestDeviceCookie(string $value): void
    {
        $cookies = $this->getWebRequest()->getCookies();
        $cookies->readOnly = false;
        $cookies->add(new Cookie(['name' => $this->getWebUser()->deviceCookie['name'], 'value' => $value]));
        $cookies->readOnly = true;
    }

    private function removeRequestDeviceCookie(): void
    {
        $cookies = $this->getWebRequest()->getCookies();
        $cookies->readOnly = false;
        $cookies->remove($this->getWebUser()->deviceCookie['name'], false);
        $cookies->readOnly = true;
    }

    private function getResponseDeviceCookie(): ?Cookie
    {
        return $this->getWebResponse()->getCookies()->get($this->getWebUser()->deviceCookie['name']);
    }

    private function createForm(string $email, string $password): LoginForm
    {
        return Yii::$container->get(LoginForm::class, [], [
            'email' => $email,
            'password' => $password,
        ]);
    }
}
