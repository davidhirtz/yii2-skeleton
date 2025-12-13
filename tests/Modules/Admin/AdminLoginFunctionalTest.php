<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Hirtz\Tenant\Test\TestCase;
use RobThree\Auth\TwoFactorAuth;
use Yii;

class AdminLoginFunctionalTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    protected function setUp(): void
    {
        parent::setUp();

        self::openUri('admin');
        self::assertUrlPathEquals('admin/account/login');
        self::assertAnyAlertErrorSame('You must login to view this page!');
    }

    protected function tearDown(): void
    {
        Yii::$app->getUser()->logout();
        parent::tearDown();
    }

    public function testLoginWithInvalidCredentials(): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;

        self::submitLoginForm();

        self::assertAnyValidationErrorSame('Email cannot be blank.');
        self::assertAnyValidationErrorSame('Password cannot be blank.');

        self::submitLoginForm($this->getUserFixtureData('owner')['email'], 'wrong');
        self::assertAnyValidationErrorSame('Your email or password are incorrect.');

        self::submitLoginForm($this->getUserFixtureData('disabled')['email'], 'password');
        self::assertAnyValidationErrorSame('Your account is currently disabled. Please contact an administrator!');

        self::submitLoginForm($this->getUserFixtureData('owner')['email'], 'password');
        self::assertResponseStatusCodeSame(403);
    }

    public function testLoginWithAdminPermission(): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;

        $user = $this->getUserFromFixture('owner');
        $this->assignAdminRole($user->id);

        self::submitLoginForm($user->email, 'password');
        self::assertResponseStatusCodeSame(200);
        self::assertUrlPathEquals('admin');
    }

    public function testLoginAsOwner(): void
    {
        $user = $this->getUserFromFixture('owner');

        self::submitLoginForm($user->email, 'password');
        self::assertResponseStatusCodeSame(200);
        self::assertUrlPathEquals('admin');
    }

    public function testLoginWithTwoFactorAuthentication(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->assignAdminRole($user->id);

        self::submitLoginForm($user->email, 'password');
        self::assertSelectorTextSame('.card-title', 'Two-Factor Authentication');

        self::submitLoginForm(code: '');
        self::assertAnyValidationErrorSame('Code should contain 6 characters.');

        self::submitLoginForm(code: '000000');
        self::assertAnyValidationErrorSame('Code is invalid.');

        $validator = Yii::createObject(TwoFactorAuthenticationValidator::class);
        $auth = new TwoFactorAuth(null, $validator->length, $validator->period);

        self::submitLoginForm(code: $auth->getCode($user->google_2fa_secret));
        self::assertResponseStatusCodeSame(200);
        self::assertUrlPathEquals('admin');
    }

    public function testDisabledLogin(): void
    {
        Yii::$app->getUser()->enableLogin = false;
        $user = $this->getUserFromFixture('owner');

        self::submitLoginForm($user->email, 'password');
        self::assertAnyValidationErrorSame('Sorry, logging in is currently disabled!');
    }

    public static function submitLoginForm(?string $email = null, ?string $password = null, ?string $code = null): void
    {
        $form = self::$crawler->selectButton('Login')
            ->form()
            ->setValues(array_filter([
                Html::getInputName(LoginForm::instance(), 'email') => $email,
                Html::getInputName(LoginForm::instance(), 'password') => $password,
                Html::getInputName(LoginForm::instance(), 'code') => $code,
            ]));

        self::$crawler = self::$browser->submit($form);
    }
}
