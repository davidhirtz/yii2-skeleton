<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\tests\unit\fixtures\UserFixture;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use Yii;

/**
 * Class LoginFormTest
 * @package davidhirtz\yii2\skeleton\tests\unit\models
 */
class LoginFormTest extends \Codeception\Test\Unit
{
    /**
     * @return array
     */
    public function _fixtures()
    {
        return [
            'users' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
        ];
    }

    public function testLoginNoUser()
    {
        $form = new LoginForm([
            'email' => 'not_existing_email',
            'password' => 'not_existing_password',
        ]);

        static::assertFalse($form->validate(), 'model should not login user');
    }

    public function testLoginWrongPassword()
    {
        $form = new LoginForm([
            'email' => 'owner@domain.com',
            'password' => 'wrong_password',
        ]);

        static::assertFalse($form->validate(), 'model should not login user');
        static::assertArrayHasKey('email', $form->getErrors(), 'error message should be set');
    }

    public function testLoginCorrect()
    {
        $form = new LoginForm([
            'email' => 'owner@domain.com',
            'password' => 'password',
        ]);

        static::assertTrue($form->validate(), 'model should login user');
        static::assertEmpty($form->getErrors(), 'error messages should not be set');
    }

    public function testLoginWithGoogleAuthenticatorEmptyCode()
    {
        $form = new LoginForm([
            'email' => 'f2a@domain.com',
            'password' => 'password',
        ]);

        static::assertFalse($form->validate(), 'model should not login user');
        static::assertArrayHasKey('code', $form->getErrors(), 'error message should be set');
    }

    public function testLoginWithGoogleAuthenticatorInvalidCode()
    {
        $form = new LoginForm([
            'email' => 'f2a@domain.com',
            'password' => 'password',
            'code' => 123456,
        ]);

        static::assertFalse($form->validate(), 'model should not login user');
        static::assertArrayHasKey('code', $form->getErrors(), 'error message should be set');
    }

    public function testLoginWithGoogleAuthenticatorCorrect()
    {
        Yii::$container->set(GoogleAuthenticatorValidator::class, [
            'currentTime' => 1609455600,
        ]);

        $form = new LoginForm([
            'email' => 'f2a@domain.com',
            'password' => 'password',
            'code' => '492042',
        ]);

        static::assertTrue($form->validate(), 'model should login user');
        static::assertEmpty($form->getErrors(), 'error messages should not be set');
    }
}