<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\unit\models\forms;

use Codeception\Test\Unit;
use Hirtz\Skeleton\codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\models\forms\LoginForm;
use Hirtz\Skeleton\validators\TwoFactorAuthenticationValidator;
use Yii;

class LoginFormTest extends Unit
{
    use UserFixtureTrait;

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
        Yii::$container->set(TwoFactorAuthenticationValidator::class, [
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
