<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

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

    private const string DELETE_FORM_SELECTOR = 'form[hx-post="/admin/account/delete"]';

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

    public function testDeleteButtonIsHiddenFromTheOwner(): void
    {
        $this->login('owner');
        $this->open('admin/account/update');

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists(self::DELETE_FORM_SELECTOR);
    }

    public function testDeleteButtonOfDeletableUser(): void
    {
        Yii::$app->getUser()->enableTwoFactorAuthentication = false;

        $this->login('admin');
        $this->open('admin/account/update');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(self::DELETE_FORM_SELECTOR . ' input[name="value"][type="password"]');

        // The password is verified, never compared, so it must not reach the markup as the input's `pattern`
        self::assertSelectorNotExists(self::DELETE_FORM_SELECTOR . ' input[pattern]');
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
        $this->postDeleteForm('wrong');

        // A form the action failed to load would redirect just the same, but without an error
        self::assertAlertSame(Yii::t('yii', '{attribute} is invalid.', [
            'attribute' => $this->getUserFromFixture('admin')->getAttributeLabel('password'),
        ]), 'danger');

        self::assertNotNull(User::findOne($id));
    }

    /**
     * The delete button posts through htmx, so its form carries no `action` for the browser to follow.
     */
    protected function postDeleteForm(string $password): void
    {
        $request = Yii::$app->getRequest();

        self::$crawler = self::$client->request('POST', 'https://www.test.localhost/admin/account/delete', [
            'value' => $password,
            $request->csrfParam => $request->getCsrfToken(),
        ]);
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
