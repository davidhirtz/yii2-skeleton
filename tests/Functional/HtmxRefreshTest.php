<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

/**
 * A page whose session is gone cannot be patched, so htmx is told to load it again instead.
 */
class HtmxRefreshTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    private const array HTMX_SERVER = ['HTTP_HX_REQUEST' => 'true'];

    public function testLogoutRefreshesTheHtmxPage(): void
    {
        $this->login();
        $this->post('/admin/account/logout', self::HTMX_SERVER);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseHeaderSame('hx-refresh', 'true');
        self::assertResponseNotHasHeader('hx-location');
    }

    public function testLogoutWithoutHtmxStillRedirects(): void
    {
        $this->login();

        self::$client->followRedirects(false);
        $this->post('/admin/account/logout');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseNotHasHeader('hx-refresh');
        self::assertResponseHasHeader('location');
    }

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

    protected function login(): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));
    }

    protected function post(string $uri, array $server = []): void
    {
        $request = Yii::$app->getRequest();

        self::$crawler = self::$client->request(
            'POST',
            "https://www.test.localhost$uri",
            [$request->csrfParam => $request->getCsrfToken()],
            server: $server,
        );
    }
}
