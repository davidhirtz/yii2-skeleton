<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\DeleteForm;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class AccountTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testSettings(): void
    {
        $this->login('owner');
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
        $this->login('owner');
        $this->open('admin/account/credentials');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#user-email');
        self::assertSelectorExists('#accountcredentialsform-newpassword');
        self::assertSelectorExists('#accountcredentialsform-oldpassword');
        self::assertSelectorNotExists('#user-name');

        self::assertSubmenuLinks();
    }

    public function testSecurity(): void
    {
        $this->login('owner');
        $this->open('admin/account/security');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#googleauthenticator-code');

        self::assertSubmenuLinks();
    }

    public function testDisabledSecurity(): void
    {
        $this->login('owner');
        Yii::$app->getUser()->enableTwoFactorAuthentication = false;

        $this->open('admin/account/security');
        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteFormOfDeletableUser(): void
    {
        Yii::$app->getUser()->enableTwoFactorAuthentication = false;

        $this->login('admin');
        $this->open('admin/account/update');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('#deleteform-value[type="password"]');

        // The confirmation is verified, never compared, so the form must not carry the password as a `pattern`
        self::assertSelectorNotExists('#deleteform-value[pattern]');
    }

    /**
     * The successful delete lives in `AccountDeleteFormTest`: logging out a user that was just deleted makes
     * `session_regenerate_id()` write a session row whose `user_id` is gone, and the warning would ride along here.
     */
    public function testDeleteAccountWithWrongPassword(): void
    {
        Yii::$app->getUser()->enableTwoFactorAuthentication = false;

        $this->login('admin');
        $id = $this->getUserFromFixture('admin')->id;

        $this->open('admin/account/update');

        $this->submit('form[action^="/admin/account/delete"]', $this->prefixFormValues(DeleteForm::instance()->formName(), [
            'value' => 'wrong',
        ]));

        self::assertResponseIsSuccessful();
        self::assertNotNull(User::findOne($id));
    }

    protected function login(string $fixtureKey): void
    {
        $this->open('admin/account/login');

        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), [
            'email' => $this->getUserFromFixture($fixtureKey)->email,
            'password' => 'password',
        ]));
    }

    private static function assertSubmenuLinks(): void
    {
        self::assertSelectorExists('.tabs a[href="/admin/account/update"]');
        self::assertSelectorExists('.tabs a[href="/admin/account/credentials"]');
        self::assertSelectorExists('.tabs a[href="/admin/account/security"]');
    }
}
