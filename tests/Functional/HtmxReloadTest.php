<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

/**
 * A swap only reaches `#wrap` and the navbar and flashes sit outside it, so an ended session reloads the document.
 */
class HtmxReloadTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private const array HTMX_SERVER = ['HTTP_HX_REQUEST' => 'true'];
    private const string LOGIN_URL = 'https://www.test.localhost/admin/account/login?returnUrl=https%3A%2F%2Fwww.test.localhost%2Fadmin%2Fuser%2Findex';

    public function testLogoutSendsTheDocumentToTheLoginPage(): void
    {
        $this->login();
        $this->post('/admin/account/logout', self::HTMX_SERVER);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('hx-redirect', 'https://www.test.localhost/admin/account/login');
        self::assertResponseNotHasHeader('hx-refresh');
        self::assertResponseNotHasHeader('hx-location');
    }

    /**
     * A swapped login left the guest navbar and the login-required error standing, since only a success alert times
     * out (#185).
     */
    public function testALoginSendsTheDocumentToTheReturnUrl(): void
    {
        $this->open('admin/user/index');
        self::assertCurrentUrlEquals(self::LOGIN_URL);

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]), server: self::HTMX_SERVER);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('hx-redirect', 'https://www.test.localhost/admin/user/index');
        self::assertResponseNotHasHeader('hx-refresh');
        self::assertResponseNotHasHeader('hx-location');
    }

    public function testLogoutWithoutHtmxStillRedirects(): void
    {
        $this->login();

        self::$client->followRedirects(false);
        $this->post('/admin/account/logout');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseNotHasHeader('hx-refresh');
        self::assertResponseNotHasHeader('hx-redirect');
        self::assertResponseHasHeader('location');
    }

    /**
     * A redirect to the login would carry no return URL, since Yii records one for a GET alone.
     */
    public function testAGuestHtmxRequestRefreshesInsteadOfLoggingIn(): void
    {
        $this->open('admin/user/index');
        self::assertCurrentUrlEquals(self::LOGIN_URL);

        self::$crawler = self::$client->request(
            'GET',
            'https://www.test.localhost/admin/user/index',
            server: self::HTMX_SERVER,
        );

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('hx-refresh', 'true');
        self::assertResponseNotHasHeader('hx-location');
    }

    /**
     * `Test\Browser` once merged each server bag into `$_SERVER` and `Web\Response` kept its refresh flag across
     * `clear()`, so one htmx request made every later response an empty `HX-Refresh`.
     */
    public function testAnHtmxRequestDoesNotOutliveItself(): void
    {
        $this->open('admin/account/login');

        self::$client->request(
            'GET',
            'https://www.test.localhost/admin/user/index',
            server: self::HTMX_SERVER,
        );

        self::assertResponseHeaderSame('hx-refresh', 'true');

        self::$crawler = self::$client->request('GET', 'https://www.test.localhost/admin/account/login');

        self::assertResponseNotHasHeader('hx-refresh');
        self::assertSelectorExists('form');
    }

    protected function login(): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));
    }

    /**
     * @param array<string, mixed> $server
     */
    protected function post(string $uri, array $server = []): void
    {
        $request = $this->getWebRequest();

        self::$crawler = self::$client->request(
            'POST',
            "https://www.test.localhost$uri",
            [$request->csrfParam => $request->getCsrfToken()],
            server: $server,
        );
    }
}
