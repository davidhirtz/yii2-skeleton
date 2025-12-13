<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use Yii;

class LoginFormTest extends TestCase
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

    public function testLoginWithTwoFactorAuthenticatorEmptyCode(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'f2a@domain.com',
            'password' => 'password',
        ]);

        static::assertFalse($form->validate(), 'Model should not login user');
        static::assertArrayHasKey('code', $form->getErrors(), 'Error message should be set');
    }

    public function testLoginWithTwoFactorAuthenticatorInvalidCode(): void
    {
        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => 'f2a@domain.com',
            'password' => 'password',
            'code' => 123456,
        ]);

        static::assertFalse($form->validate(), 'Model should not login user');
        static::assertArrayHasKey('code', $form->getErrors(), 'Error message should be set');
    }

    public function testLoginWithTwoFactorAuthenticatorCorrect(): void
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
