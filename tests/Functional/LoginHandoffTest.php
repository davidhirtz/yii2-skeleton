<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\Session;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\Browser;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Web\LoginHandoff;
use Override;
use Symfony\Component\BrowserKit\Cookie;
use Yii;

final class LoginHandoffTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private const string AUTH_HOST_INFO = 'https://www.auth.localhost';
    private const string HOST_INFO = 'https://www.test.localhost';
    private const string RETURN_URL = self::HOST_INFO . '/admin/account/update?tab=Å東京';

    private ?Browser $browser = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $webuser = $this->getWebUser();
        $webuser->authHostInfo = self::AUTH_HOST_INFO;
        $webuser->handoffHostInfos = [self::AUTH_HOST_INFO, self::HOST_INFO];
    }

    public function testAGuestLogsInOnTheAuthHostAndReturns(): void
    {
        $user = $this->getUserFromFixture('owner');

        // `open()` would pin the host, and the auth host's relative form action would post to this one
        $this->request(self::HOST_INFO . '/admin/account/update', followRedirects: true);

        self::assertStringStartsWith(self::AUTH_HOST_INFO . '/admin/account/login', self::$client->getHistory()->current()->getUri());
        self::assertNotNull(self::$client->getCookieJar()->get('_handoff', '/', 'www.test.localhost'));

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $user->email,
            'password' => 'password',
        ]));

        self::assertSame(self::HOST_INFO . '/admin/account/update', self::$client->getHistory()->current()->getUri());
        self::assertSame($user->id, $this->getWebUser()->getId());
        self::assertSame(UserLogin::TYPE_HANDOFF, $this->getLastLoginType($user));
        self::assertSame(0, (int)UserToken::find()->whereType(UserToken::TYPE_LOGIN_HANDOFF)->count());
    }

    public function testTheLoginFormStaysWithoutAnAuthHost(): void
    {
        $this->getWebUser()->authHostInfo = null;
        $this->open(self::HOST_INFO . '/admin/account/login');

        self::assertSelectorExists('input[name="Login[password]"]');
    }

    public function testTheLoginFormStaysOnTheAuthHost(): void
    {
        $this->open(self::AUTH_HOST_INFO . '/admin/account/login');
        self::assertSelectorExists('input[name="Login[password]"]');
    }

    public function testAnUnknownHostShowsItsOwnLoginForm(): void
    {
        $this->open('https://www.unknown.localhost/admin/account/login');
        self::assertSelectorExists('input[name="Login[password]"]');
    }

    public function testTheCallbackLogsInOnceAndRegeneratesTheSession(): void
    {
        $user = $this->getUserFromFixture('owner');
        $callbackUrl = $this->getCallbackUrl($user);

        $user->updateAttributes(['login_count' => 3]);
        $lastLogin = User::findOne($user->id)?->last_login;
        $sessionId = $this->getWebSession()->getId();

        $this->request($callbackUrl);

        self::assertResponseStatusCodeSame(302);
        self::assertResponseHeaderSame('location', self::RETURN_URL);
        self::assertResponseHeaderSame('referrer-policy', 'no-referrer');
        self::assertSame($user->id, $this->getWebUser()->getId());
        self::assertNotSame($sessionId, $this->getWebSession()->getId());
        self::assertSame($this->getQueryParam($callbackUrl, 'tag'), $this->getWebSession()->get(LoginHandoff::create()->tagSessionParam));
        self::assertNull(self::$client->getCookieJar()->get('_handoff', '/', 'www.test.localhost'));

        // The login was counted on the auth host; the hand-off only records where it went.
        $user = User::findOne($user->id) ?? self::fail('No user.');
        self::assertSame(UserLogin::TYPE_HANDOFF, $this->getLastLoginType($user));
        self::assertSame(3, $user->login_count);
        self::assertEquals($lastLogin, $user->last_login);
    }

    /**
     * The back button to a spent callback finds the user logged in already.
     */
    public function testASpentTokenLeadsALoggedInUserOn(): void
    {
        $callbackUrl = $this->getCallbackUrl($this->getUserFromFixture('owner'));

        $this->request($callbackUrl);
        $this->request($callbackUrl);

        self::assertResponseStatusCodeSame(302);
        self::assertResponseHeaderSame('location', self::RETURN_URL);
    }

    public function testAnExpiredTokenStartsTheHandOffOnce(): void
    {
        $callbackUrl = $this->getCallbackUrl($this->getUserFromFixture('owner'));
        $this->getWebUser()->logout();
        UserToken::updateAll(['expires_at' => gmdate('Y-m-d H:i:s', time() - 1)], ['type' => UserToken::TYPE_LOGIN_HANDOFF]);

        $this->assertCallbackRetries($callbackUrl);
    }

    public function testATamperedReturnLogsNobodyIn(): void
    {
        $callbackUrl = $this->getCallbackUrl($this->getUserFromFixture('owner'));
        $this->getWebUser()->logout();

        $this->assertCallbackRetries(str_replace(rawurlencode('update'), rawurlencode('delete'), $callbackUrl));
    }

    /**
     * An expired state cookie, or a second tab's in its place.
     */
    public function testAMissingOrForeignStateCookieStartsTheHandOffAgain(): void
    {
        $callbackUrl = $this->getCallbackUrl($this->getUserFromFixture('owner'));
        $this->getWebUser()->logout();
        $jar = self::$client->getCookieJar();

        $jar->set(new Cookie('_handoff', $this->getSignedCookieValue('_handoff', 'another-tabs-state'), domain: 'www.test.localhost', secure: true));
        $this->assertCallbackRetries($callbackUrl);

        $jar->clear();
        $this->assertCallbackRetries($callbackUrl);
    }

    public function testTheCallbackOnAnotherHostIsRefused(): void
    {
        $callbackUrl = $this->getCallbackUrl($this->getUserFromFixture('owner'));

        $this->request(str_replace(self::HOST_INFO, self::AUTH_HOST_INFO, $callbackUrl));
        self::assertResponseStatusCodeSame(400);
    }

    public function testADisabledUserIsRefused(): void
    {
        $user = $this->getUserFromFixture('owner');
        $callbackUrl = $this->getCallbackUrl($user);

        $this->getWebUser()->logout();
        $user->updateAttributes(['status' => User::STATUS_DISABLED]);

        $this->assertCallbackRetries($callbackUrl);
    }

    public function testAnUnknownReturnIsRefusedBeforeATokenExists(): void
    {
        $this->getWebUser()->login($this->getUserFromFixture('owner'));
        $this->getWebRequest()->allowedHosts = ['*'];

        $this->request(self::AUTH_HOST_INFO . '/admin/sso?' . http_build_query([
            'return' => 'https://www.evil.localhost/admin/account/update',
            'state' => str_repeat('a', 32),
        ]));

        self::assertResponseStatusCodeSame(400);
        self::assertSame(0, (int)UserToken::find()->whereType(UserToken::TYPE_LOGIN_HANDOFF)->count());
    }

    public function testOnlyTheAuthHostIssuesTokens(): void
    {
        $this->getWebUser()->login($this->getUserFromFixture('owner'));

        $this->request(self::HOST_INFO . '/admin/sso?' . http_build_query([
            'return' => self::RETURN_URL,
            'state' => str_repeat('a', 32),
        ]));

        self::assertResponseStatusCodeSame(404);
    }

    public function testTheLogoutEndsTheHandedOutSessionsOnly(): void
    {
        $user = $this->getUserFromFixture('owner');
        $tag = str_repeat('t', 32);

        $this->insertSession('handed-out', $user, $tag);
        $this->insertSession('another-browser', $user, null);

        $url = LoginHandoff::create()->getLogoutUrl($tag, $user->id, self::HOST_INFO . '/admin/account/login');

        $this->request(str_replace('signature=', 'signature=0', $url));
        self::assertResponseStatusCodeSame(400);
        self::assertTrue(Session::find()->where(['id' => 'handed-out'])->exists());

        $this->request($url);

        self::assertResponseStatusCodeSame(302);
        self::assertResponseHeaderSame('location', self::HOST_INFO . '/admin/account/login');
        self::assertFalse(Session::find()->where(['id' => 'handed-out'])->exists());
        self::assertTrue(Session::find()->where(['id' => 'another-browser'])->exists());
    }

    /**
     * A remember-me login renews the auth host's session id between the hand-off and the logout (#798).
     */
    public function testTheLogoutOnTheHandOffHostEndsTheRenewedAuthSession(): void
    {
        $user = $this->getUserFromFixture('owner');
        $this->request($this->getCallbackUrl($user));

        $this->request(self::HOST_INFO . '/admin/account/logout', method: 'POST');
        $logoutUrl = $this->getLocation();
        self::assertStringStartsWith(self::AUTH_HOST_INFO . '/admin/sso/logout?', $logoutUrl);
        self::assertTrue($this->getWebUser()->getIsGuest());

        $this->getWebUser()->login($user);
        $this->getWebSession()->regenerateID(true);

        $this->request($logoutUrl);

        self::assertResponseStatusCodeSame(302);
        self::assertTrue($this->getWebUser()->getIsGuest());
    }

    public function testTheLogoutLeavesAnotherAccountLoggedIn(): void
    {
        $owner = $this->getUserFromFixture('owner');
        $admin = $this->getUserFromFixture('admin');

        $this->getWebUser()->login($admin);
        $this->request(LoginHandoff::create()->getLogoutUrl(str_repeat('t', 32), $owner->id, self::HOST_INFO . '/admin/account/login'));

        self::assertSame($admin->id, $this->getWebUser()->getId());
    }

    /**
     * The tag is the auth host's cookie, which outlives a renewed or expired session (#798).
     */
    public function testTheAuthHostsLogoutEndsTheSessionsItHandedOut(): void
    {
        $user = $this->getUserFromFixture('owner');
        $tag = $this->getQueryParam($this->getCallbackUrl($user), 'tag');

        $this->insertSession('handed-out', $user, $tag);
        $this->getWebSession()->regenerateID(true);

        // A page of the auth host first, which carries the CSRF cookie the logout is posted with
        $this->request(self::AUTH_HOST_INFO . '/admin/account/update');
        $this->request(self::AUTH_HOST_INFO . '/admin/account/logout', method: 'POST');

        self::assertTrue($this->getWebUser()->getIsGuest());
        self::assertFalse(Session::find()->where(['id' => 'handed-out'])->exists());
    }

    public function testTheSessionCookieStaysLaxForTheCrossSiteBounce(): void
    {
        self::assertSame('Lax', $this->getWebSession()->getCookieParams()['sameSite']);
    }

    /**
     * Starts on the hand-off host as a guest, which sets the state cookie, and asks the auth host for the token.
     */
    private function getCallbackUrl(User $user): string
    {
        $this->request(self::HOST_INFO . '/admin/account/login?' . http_build_query([
            'returnUrl' => self::RETURN_URL,
        ]));

        $startUrl = $this->getLocation();
        self::assertStringStartsWith(self::AUTH_HOST_INFO . '/admin/sso?', $startUrl);

        $this->getWebUser()->login($user);
        $this->request($startUrl);

        $callbackUrl = $this->getLocation();
        self::assertStringStartsWith(self::HOST_INFO . '/admin/sso/callback?', $callbackUrl);

        return $callbackUrl;
    }

    /**
     * A failed callback starts the hand-off again once, then gives up.
     */
    private function assertCallbackRetries(string $callbackUrl): void
    {
        $this->request($callbackUrl);

        self::assertResponseStatusCodeSame(302);
        self::assertStringStartsWith(self::HOST_INFO . '/admin/account/login?', $this->getLocation());
        self::assertSame('1', $this->getQueryParam($this->getLocation(), 'retry'));
        self::assertTrue($this->getWebUser()->getIsGuest());

        $this->request("$callbackUrl&retry=1");
        self::assertResponseStatusCodeSame(400);
    }

    private function getQueryParam(string $url, string $name): string
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $params);
        $value = $params[$name] ?? null;
        self::assertIsString($value);

        return $value;
    }

    private function request(string $uri, string $method = 'GET', bool $followRedirects = false): void
    {
        $this->browser ??= new Browser(['HTTPS' => 'on']);
        $this->browser->followRedirects($followRedirects);

        self::$client = $this->browser;

        $parameters = $method === 'POST' ? ['_csrf' => $this->getWebRequest()->getCsrfToken()] : [];
        self::$crawler = self::$client->request($method, $uri, $parameters);
    }

    private function insertSession(string $id, User $user, ?string $tag): void
    {
        Session::getDb()->createCommand()->insert(Session::tableName(), [
            'id' => $id,
            'user_id' => $user->id,
            'handoff_tag' => $tag,
            'expire' => time() + 3600,
        ])->execute();
    }

    private function getLastLoginType(User $user): int
    {
        return (int)UserLogin::find()
            ->select('type')
            ->where(['user_id' => $user->id])
            ->orderBy(['id' => SORT_DESC])
            ->scalar();
    }

    private function getSignedCookieValue(string $name, string $value): string
    {
        $key = $this->getWebRequest()->cookieValidationKey;
        return Yii::$app->getSecurity()->hashData(serialize([$name, $value]), $key);
    }

    private function getLocation(): string
    {
        $location = self::$client->getResponse()->getHeader('location');
        self::assertIsString($location);

        return $location;
    }
}
