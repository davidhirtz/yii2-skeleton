<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\Browser;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

final class LoginRequiredTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testACookielessRequestGetsNoSessionAndKeepsItsReturnUrlInTheQuery(): void
    {
        $count = $this->countQueries(function (): void {
            self::$client = new Browser(['HTTP_HOST' => 'www.test.localhost', 'HTTPS' => 'on']);
            self::$client->followRedirects(false);
            self::$client->request('GET', '/admin/user/index');
        }, '/`session`/');

        self::assertResponseStatusCodeSame(302);
        self::assertResponseHeaderSame('location', 'https://www.test.localhost/admin/account/login?returnUrl='
            . urlencode('https://www.test.localhost/admin/user/index'));

        self::assertSame(0, $count);
        self::assertFalse($this->getWebSession()->getIsActive());
    }

    public function testTheLoginFollowsTheReturnUrlFromTheQuery(): void
    {
        $this->open('admin/user/index');
        $this->submitLoginForm();

        self::assertCurrentUrlEquals('https://www.test.localhost/admin/user/index');
    }

    public function testTheLoginIgnoresAReturnUrlOnAnotherHost(): void
    {
        $this->open('admin/account/login?returnUrl=' . urlencode('https://www.test.localhost.example.com/'));
        $this->submitLoginForm();

        self::assertCurrentUrlEquals('https://www.test.localhost/admin/dashboard/index');
    }

    public function testARequestWithASessionKeepsTheFlashAndTheSessionReturnUrl(): void
    {
        $this->getWebSession()->set('started', true);
        $this->open('admin/user/index');

        self::assertCurrentUrlEquals('https://www.test.localhost/admin/account/login');
        self::assertAnyAlertErrorSame(Yii::t('skeleton', 'USER_ERROR_MUST_LOGIN_VIEW'));
        self::assertSame('https://www.test.localhost/admin/user/index', $this->getWebUser()->getReturnUrl());
    }

    public function testIsReturnUrl(): void
    {
        $webuser = $this->getWebUser();

        self::assertTrue($webuser->isReturnUrl('/admin/user/index'));
        self::assertTrue($webuser->isReturnUrl('https://www.test.localhost/admin'));
        self::assertTrue($webuser->isReturnUrl('https://draft.test.localhost/page'));

        self::assertFalse($webuser->isReturnUrl('//example.com/'));
        self::assertFalse($webuser->isReturnUrl('/\\example.com/'));
        self::assertFalse($webuser->isReturnUrl('https://example.com/'));
        self::assertFalse($webuser->isReturnUrl('https://www.test.localhost.example.com/'));
        self::assertFalse($webuser->isReturnUrl("/admin\n"));
    }

    private function submitLoginForm(): void
    {
        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture('owner')->email,
            'password' => 'password',
        ]));
    }
}
