<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

/**
 * A page rendered for a session that is over — a guest's as much as a gone one — cannot be patched, because a swap
 * only ever reaches `#wrap` and the navbar and the flashes sit outside it. So htmx is told to load a fresh document
 * instead, at the URL the response redirects to or at the one it is already on.
 */
class HtmxReloadTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private const array HTMX_SERVER = ['HTTP_HX_REQUEST' => 'true'];

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
     * A login that swapped left the guest navbar standing and the login-required error in `#flashes` behind it,
     * where nothing removes it: only a success alert times out (monorepo issue #185).
     */
    public function testALoginSendsTheDocumentToTheReturnUrl(): void
    {
        $this->open('admin/user/index');
        self::assertCurrentUrlEquals('https://www.test.localhost/admin/account/login');
        self::assertAnyAlertErrorSame(Yii::t('skeleton', 'USER_ERROR_MUST_LOGIN_VIEW'));

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
     * The refresh is the whole of the answer here: a redirect to the login page would carry no return URL, since
     * Yii records one for a GET alone, and would lose a form post to a session that lapsed under it.
     */
    public function testAGuestHtmxRequestRefreshesInsteadOfLoggingIn(): void
    {
        $this->open('admin/user/index');
        self::assertCurrentUrlEquals('https://www.test.localhost/admin/account/login');

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
     * Two pieces of per-request state used to reach the request after it: `Test\Browser` merged each request's
     * server bag into the process-wide `$_SERVER`, so one htmx request made the rest of the test an htmx
     * session, and `Web\Response` kept its refresh flag across a `clear()`, so every later response was an empty
     * 200 carrying `HX-Refresh` — which reads as a request that silently did nothing.
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
