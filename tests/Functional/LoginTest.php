<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Override;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;

class LoginTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->open('admin/account/login');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->getWebUser()->disableRbacForOwner = false;

        $this->submitLoginForm();
        self::assertAnyValidationErrorSame('Email cannot be blank.');
        self::assertAnyValidationErrorSame('Password cannot be blank.');

        $this->submitLoginForm($this->getUserFixtureData('owner')['email'], 'wrong');
        self::assertAnyValidationErrorSame('Your email or password are incorrect.');

        // A disabled account reports the same as a wrong password, so nothing here says which addresses exist
        $this->submitLoginForm($this->getUserFixtureData('disabled')['email'], 'password');
        self::assertAnyValidationErrorSame('Your email or password are incorrect.');

        $this->submitLoginForm('never-signed-up@domain.com', 'password');
        self::assertAnyValidationErrorSame('Your email or password are incorrect.');

        $this->submitLoginForm($this->getUserFixtureData('owner')['email'], 'password');
        self::assertResponseStatusCodeSame(403);
    }

    public function testLoginWithAdminPermission(): void
    {
        $this->getWebUser()->disableRbacForOwner = false;

        $user = $this->getUserFromFixture('owner');
        $this->assignAdminRole($user->id);

        $this->submitLoginForm($user->email, 'password');
        self::assertResponseStatusCodeSame(200);
        self::assertCurrentUrlEquals('admin/dashboard/index');
    }

    public function testLoginAsOwner(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->submitLoginForm($user->email, 'password');
        self::assertResponseStatusCodeSame(200);

        self::assertCurrentUrlEquals('admin/dashboard/index');
    }

    public function testLoginWithTwoFactorAuthentication(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        $this->submitLoginForm($user->email, 'password');
        self::assertSelectorTextSame('.card-title', 'Two-Factor Authentication');

        $this->submitLoginForm(code: '');
        self::assertAnyValidationErrorSame('Code should contain 6 characters.');

        $this->submitLoginForm(code: '000000');
        self::assertAnyValidationErrorSame('Code is invalid.');

        $validator = Yii::createObject(TwoFactorAuthenticationValidator::class);
        $auth = new TwoFactorAuth(new QRServerProvider(), digits: $validator->length, period: $validator->period);

        $this->submitLoginForm(code: $auth->getCode($user->getTwoFactorAuthenticationSecret()));
        self::assertResponseStatusCodeSame(200);

        self::assertCurrentUrlEquals('admin/dashboard/index');
    }

    public function testDisabledAccountNamesItsReasonWithoutEnumerationProtection(): void
    {
        $this->getWebUser()->enableUserEnumerationProtection = false;

        $this->submitLoginForm($this->getUserFixtureData('disabled')['email'], 'password');
        self::assertAnyValidationErrorSame('Your account is currently disabled. Please contact an administrator!');

        $this->submitLoginForm('never-signed-up@domain.com', 'password');
        self::assertAnyValidationErrorSame('Your email was not found.');
    }

    public function testDisabledLogin(): void
    {
        $this->getWebUser()->enableLogin = false;
        $user = $this->getUserFromFixture('owner');

        $this->submitLoginForm($user->email, 'password');
        self::assertAnyValidationErrorSame('Sorry, logging in is currently disabled!');
    }

    /**
     * The login page is what renders the flash, so one `loginRequired()` adds after it was drawn — from a
     * background htmx request, which is answered with a refresh rather than a body — is read by the page after
     * the login instead: a flash removed after access is removed only once something reads it.
     */
    public function testALoginRequiredFlashDoesNotOutliveTheLogin(): void
    {
        $login = self::$crawler;

        self::$client->request(
            'GET',
            'https://www.test.localhost/admin/user/index',
            server: ['HTTP_HX_REQUEST' => 'true'],
        );

        self::assertResponseHeaderSame('hx-refresh', 'true');
        self::assertSame('', self::$client->getResponse()->getContent());

        self::$crawler = $login;
        $this->submitLoginForm($this->getUserFromFixture('owner')->email, 'password');

        self::assertCurrentUrlEquals('admin/dashboard/index');
        self::assertSelectorNotExists('[data-alert="error"]');
    }

    private function submitLoginForm(?string $email = null, ?string $password = null, ?string $code = null): void
    {
        $this->submit(values: $this->prefixFormValues(LoginForm::instance()->formName(), array_filter([
            'email' => $email,
            'password' => $password,
            'code' => $code,
        ])));
    }
}
