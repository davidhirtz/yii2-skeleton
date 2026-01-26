<?php

declare(strict_types=1);

/**
 * @noinspection PhpUnused
 */

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorLoginActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;

class LoginCest extends BaseCest
{
    use UserFixtureTrait;

    public function _before(): void
    {
        Yii::$app->getUser()->enableLogin = true;
        Yii::$app->getUser()->enableGoogleAuthenticator = true;

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

        $user = $I->grabFixture('user', 'owner');
        $this->assignAdminRole($user['id']);

        $this->submitLoginForm($I, 'owner@domain.com', 'password');

        $I->seeLink(Yii::t('skeleton', 'Logout'));
    }

    public function checkLoginAsOwner(FunctionalTester $I): void
    {
        Yii::$app->getUser()->disableRbacForOwner = true;

        $this->submitLoginForm($I, 'owner@domain.com', 'password');

        $I->seeLink(Yii::t('skeleton', 'Logout'));
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

        $user = $I->grabFixture('user', 'admin');
        $this->assignAdminRole($user['id']);

        $validator = Yii::createObject(GoogleAuthenticatorValidator::class);
        $auth = new TwoFactorAuth(new QRServerProvider(), null, $validator->length, $validator->period);

        $this->submitGoogleAuthenticatorForm($I, $auth->getCode($user['google_2fa_secret']));
        $I->seeLink(Yii::t('skeleton', 'Logout'));
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

        $widget = Yii::createObject(LoginActiveForm::class);

        $I->submitForm("#$widget->id", [
            Html::getInputName($widget->model, 'email') => $email,
            Html::getInputName($widget->model, 'password') => $password,
        ]);
    }

    protected function submitGoogleAuthenticatorForm(FunctionalTester $I, string $code): void
    {
        $widget = Yii::createObject(GoogleAuthenticatorLoginActiveForm::class);

        $I->submitForm("#$widget->id", [
            Html::getInputName($widget->model, 'code') => $code,
        ]);
    }
}
