<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class AccountTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->open('admin/account/login');

        $user = $this->getUserFromFixture('owner');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $user->email,
            'password' => 'password',
        ]));
    }

    public function testSettings(): void
    {
        $this->open('admin/account/update');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#user-name');
        self::assertSelectorExists('#user-language');
        self::assertSelectorExists('#user-timezone');
        self::assertSelectorNotExists('#user-email');

        self::assertSubmenuLinks();
    }

    public function testCredentials(): void
    {
        $this->open('admin/account/credentials');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#user-email');
        self::assertSelectorExists('#accountupdateform-newpassword');
        self::assertSelectorExists('#accountupdateform-oldpassword');
        self::assertSelectorNotExists('#user-name');

        self::assertSubmenuLinks();
    }

    public function testSecurity(): void
    {
        $this->open('admin/account/security');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#googleauthenticator-code');

        self::assertSubmenuLinks();
    }

    public function testDisabledSecurity(): void
    {
        Yii::$app->getUser()->enableTwoFactorAuthentication = false;

        $this->open('admin/account/security');
        self::assertResponseStatusCodeSame(403);
    }

    private static function assertSubmenuLinks(): void
    {
        self::assertSelectorExists('.tabs a[href="/admin/account/update"]');
        self::assertSelectorExists('.tabs a[href="/admin/account/credentials"]');
        self::assertSelectorExists('.tabs a[href="/admin/account/security"]');
    }
}
