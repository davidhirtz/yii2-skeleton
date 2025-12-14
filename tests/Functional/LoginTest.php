<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Hirtz\Skeleton\Test\TestCase;
use RobThree\Auth\TwoFactorAuth;
use Yii;

class LoginTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->open('admin/account/login');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;

        $this->submitLoginForm();
        self::assertAnyValidationErrorSame('Email cannot be blank.');
        self::assertAnyValidationErrorSame('Password cannot be blank.');

        $this->submitLoginForm($this->getUserFixtureData('owner')['email'], 'wrong');
        self::assertAnyValidationErrorSame('Your email or password are incorrect.');

        $this->submitLoginForm($this->getUserFixtureData('disabled')['email'], 'password');
        self::assertAnyValidationErrorSame('Your account is currently disabled. Please contact an administrator!');

        $this->submitLoginForm($this->getUserFixtureData('owner')['email'], 'password');
        self::assertResponseStatusCodeSame(403);
    }

    public function testLoginWithAdminPermission(): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;

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
        $auth = new TwoFactorAuth(null, $validator->length, $validator->period);

        $this->submitLoginForm(code: $auth->getCode($user->google_2fa_secret));
        self::assertResponseStatusCodeSame(200);

        self::assertCurrentUrlEquals('admin/dashboard/index');
    }

    public function testDisabledLogin(): void
    {
        Yii::$app->getUser()->enableLogin = false;
        $user = $this->getUserFromFixture('owner');

        $this->submitLoginForm($user->email, 'password');
        self::assertAnyValidationErrorSame('Sorry, logging in is currently disabled!');
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
