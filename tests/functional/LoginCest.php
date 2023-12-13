<?php

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\tests\fixtures\UserFixture;
use FunctionalTester;
use Yii;

class LoginCest
{
//    /**
//     * @see \Codeception\Module\Yii2::_before()
//     * @see \Codeception\Module\Yii2::loadFixtures()
//     */
//    public function _fixtures(): array
//    {
//        return [
//            'user' => [
//                'class' => UserFixture::class,
//                'dataFile' => codecept_data_dir() . 'user.php',
//            ],
//        ];
//    }

    public function checkEmpty(FunctionalTester $I): void
    {
        $I->amOnRoute('admin/account/login');
        $form = Yii::createObject(LoginActiveForm::class);

//        $I->submitForm("#$form->id", $this->getFormParams('', ''));
//        $I->seeValidationError('Username cannot be blank.');
//        $I->seeValidationError('Password cannot be blank.');
    }


    protected function getFormParams(string $email, string $password): array
    {
        $form = Yii::createObject(LoginForm::class);

        return [
            Html::getInputName($form, 'email') => $email,
            Html::getInputName($form, 'password') => $password,
        ];
    }

//    public function checkWrongPassword(FunctionalTester $I)
//    {
//        $I->submitForm('#login-form', $this->getFormParams('admin', 'wrong'));
//        $I->seeValidationError('Incorrect username or password.');
//    }
//
//    public function checkInactiveAccount(FunctionalTester $I)
//    {
//        $I->submitForm('#login-form', $this->getFormParams('test.test', 'Test1234'));
//        $I->seeValidationError('Incorrect username or password');
//    }
//
//    public function checkValidLogin(FunctionalTester $I)
//    {
//        $I->submitForm('#login-form', $this->getFormParams('erau', 'password_0'));
//        $I->see('Logout (erau)', 'form button[type=submit]');
//        $I->dontSeeLink('Login');
//        $I->dontSeeLink('Signup');
//    }
}