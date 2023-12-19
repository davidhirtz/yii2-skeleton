<?php

namespace davidhirtz\yii2\skeleton\tests\unit\models;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\tests\fixtures\UserFixture;
use davidhirtz\yii2\skeleton\validators\GoogleAuthenticatorValidator;
use Yii;

class LoginFormTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'users' => [
                'class' => UserFixture::class,
                'dataFile' => codecept_data_dir() . 'user.php'
            ],
        ];
    }

    public function testLoginNoUser(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'not_existing_email',
            'password' => 'not_existing_password',
        ]);

        static::assertFalse($form->validate(), 'Model should not login user');
    }

    public function testLoginWrongPassword(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'owner@domain.com',
            'password' => 'wrong_password',
        ]);

        static::assertFalse($form->validate(), 'Model should not login user');
        static::assertArrayHasKey('email', $form->getErrors(), 'Error message should be set');
    }

    public function testLoginCorrect(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'owner@domain.com',
            'password' => 'password',
        ]);

        static::assertTrue($form->validate(), 'Model should login user');
        static::assertEmpty($form->getErrors(), 'Error messages should not be set');
    }

    public function testLoginWithGoogleAuthenticatorEmptyCode(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'f2a@domain.com',
            'password' => 'password',
        ]);

        static::assertFalse($form->validate(), 'Model should not login user');
        static::assertArrayHasKey('code', $form->getErrors(), 'Error message should be set');
    }

    public function testLoginWithGoogleAuthenticatorInvalidCode(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'f2a@domain.com',
            'password' => 'password',
            'code' => 123456,
        ]);

        static::assertFalse($form->validate(), 'Model should not login user');
        static::assertArrayHasKey('code', $form->getErrors(), 'Error message should be set');
    }

    public function testLoginWithGoogleAuthenticatorCorrect(): void
    {
        Yii::$container->set(GoogleAuthenticatorValidator::class, [
            'currentTime' => 1_609_455_600,
        ]);

        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'f2a@domain.com',
            'password' => 'password',
            'code' => '492042',
        ]);

        static::assertTrue($form->validate(), 'Model should login user');
        static::assertEmpty($form->getErrors(), 'Error messages should not be set');
    }
}
