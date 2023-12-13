<?php
/**
 * @noinspection PhpUnused
 */

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\tests\fixtures\UserFixture;
use FunctionalTester;
use Yii;

class LoginCest extends BaseCest
{
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function _before(FunctionalTester $I): void
    {
        parent::_before($I);
        $I->amOnRoute('admin/account/login');
    }

    public function submitEmptyLoginForm(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, '', '');
        $I->seeValidationError('Email cannot be blank.');
        $I->seeValidationError('Password cannot be blank.');
    }

    public function submitWrongPassword(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'owner@domain.com', 'wrong');
        $I->seeValidationError(Yii::t('skeleton', 'Your email or password are incorrect.'));
    }

    public function submitDisabledCredentials(FunctionalTester $I): void
    {
        $this->submitLoginForm($I, 'disabled@domain.com', 'password');
        $I->seeValidationError(Yii::t('skeleton', 'Your account is currently disabled. Please contact an administrator!'));
    }

    // Todo successful login test

    protected function submitLoginForm(FunctionalTester $I, string $email, string $password): void
    {
        $widget = Yii::createObject(LoginActiveForm::class);

        $I->submitForm("#$widget->id", [
            Html::getInputName($widget->model, 'email') => $email,
            Html::getInputName($widget->model, 'password') => $password,
        ]);
    }
}