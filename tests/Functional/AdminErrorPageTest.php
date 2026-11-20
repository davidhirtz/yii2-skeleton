<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class AdminErrorPageTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testAMissingRecordKeepsTheAdminLayout(): void
    {
        $this->login();
        $this->open404('https://www.test.localhost/admin/user/update?id=987654', 'https://www.test.localhost/admin/user/index');

        self::assertSelectorExists('#wrap aside');
        self::assertSelectorExists('.navbar');
        self::assertSelectorExists('a.btn[href="https://www.test.localhost/admin/user/index"]');
    }

    public function testAnUnknownRouteKeepsTheAdminLayout(): void
    {
        $this->login();
        $this->open404('https://www.test.localhost/admin/nothing-here-Å東京');

        self::assertSelectorExists('#wrap aside');
        self::assertSelectorExists('main a.btn[href]');
    }

    public function testAGuestSeesThePlainErrorPage(): void
    {
        $this->open('https://www.test.localhost/admin/nothing-here');

        self::assertResponseStatusCodeSame(404);
        self::assertSelectorNotExists('#wrap');
    }

    public function testAnErrorOutsideTheAdminKeepsItsPage(): void
    {
        $this->login();
        $this->open404('https://www.test.localhost/nothing-here');

        self::assertSelectorNotExists('#wrap');
    }

    private function open404(string $url, ?string $referrer = null): void
    {
        self::$crawler = self::$client->request('GET', $url, server: $referrer ? ['HTTP_REFERER' => $referrer] : []);
        self::assertResponseStatusCodeSame(404);
    }

    private function login(): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));
    }
}
