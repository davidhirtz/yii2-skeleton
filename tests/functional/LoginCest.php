<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\functional;

use Hirtz\Skeleton\Codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\Codeception\functional\BaseCest;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Tests\support\FunctionalTester;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Override;
use RobThree\Auth\TwoFactorAuth;
use Yii;

class LoginCest extends BaseCest
{
    use UserFixtureTrait;

    #[Override]
    public function _before(): void
    {
        Yii::$app->getUser()->enableLogin = true;
        Yii::$app->getUser()->enableTwoFactorAuthentication = true;

        parent::_before();
    }

    public function checkLoginWithEmptyCredentials(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, '', '');

        $I->seeValidationError('Email cannot be blank.');
        $I->seeValidationError('Password cannot be blank.');
    }

    public function checkLoginWithWrongPassword(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'owner@domain.com', 'wrong');
        $I->seeValidationError(Yii::t('skeleton', 'Your email or password are incorrect.'));
    }

    public function checkLoginWithDisabledAccount(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'disabled@domain.com', 'password');
        $I->seeValidationError(Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
    }

    public function checkLoginWithoutAdminPermission(FunctionalTester $I): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;

        $this->submitLoginForm($I, 'owner@domain.com', 'password');

        $I->seeResponseCodeIs(403);
    }

    public function checkLoginWithAdminPermission(FunctionalTester $I): void
    {
        Yii::$app->getUser()->disableRbacForOwner = false;

        $user = $I->grabUserFixture();
        $this->assignAdminRole($user['id']);

        $this->submitLoginForm($I, 'owner@domain.com', 'password');
        $I->seeElement('.navbar-logout');
    }

    public function checkLoginAsOwner(FunctionalTester $I): void
    {
        Yii::$app->getUser()->disableRbacForOwner = true;

        $this->submitLoginForm($I, 'owner@domain.com', 'password');
        $I->seeElement('.navbar-logout');
    }

    public function checkTwoFactorLoginWithEmptyCredentials(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'f2a@domain.com', 'password');
        $this->submitGoogleAuthenticatorForm($I, '');

        $I->seeValidationError(Yii::t('skeleton', 'Code should contain 6 characters.'));
    }

    public function checkTwoFactorLoginWithWrongCode(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'f2a@domain.com', 'password');
        $this->submitGoogleAuthenticatorForm($I, '000000');

        $I->seeValidationError(Yii::t('skeleton', 'Code is invalid.'));
    }

    public function checkTwoFactorLoginWithCorrectCode(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'f2a@domain.com', 'password');

        $user = $I->grabUserFixture('admin');
        $this->assignAdminRole($user['id']);

        $validator = Yii::createObject(TwoFactorAuthenticationValidator::class);
        $auth = new TwoFactorAuth(null, $validator->length, $validator->period);

        $this->submitGoogleAuthenticatorForm($I, $auth->getCode($user['google_2fa_secret']));
        $I->seeElement('.navbar-logout');
    }

    public function checkDisabledLogin(FunctionalTester $I): void
    {
        Yii::$app->getUser()->enableLogin = false;

        $this->submitLoginForm($I, 'owner@domain.com', 'password');
        $I->seeValidationError(Yii::t('skeleton', 'Sorry, logging in is currently disabled!'));
    }

    protected function submitLoginForm(FunctionalTester $I, string $email, string $password): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $I->amOnPage("/$module->alias");

        $form = LoginForm::create();

        $I->submitForm('#login-form', [
            Html::getInputName($form, 'email') => $email,
            Html::getInputName($form, 'password') => $password,
        ]);
    }

    protected function submitGoogleAuthenticatorForm(FunctionalTester $I, string $code): void
    {
        $form = LoginForm::create();

        $I->submitForm('#authentication-form', [
            Html::getInputName($form, 'code') => $code,
        ]);
    }
}
