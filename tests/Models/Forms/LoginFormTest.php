<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use RobThree\Auth\Providers\Qr\BaconQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;
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

    /**
     * A code may match the slice after the current one, which a later login time does not reach yet.
     */
    public function testACodeOfTheNextTimesliceCannotBeReplayed(): void
    {
        $time = 1_609_455_600;
        $user = User::findOne(['email' => 'f2a@domain.com']);
        self::assertNotNull($user);

        $auth = new TwoFactorAuth(new BaconQrCodeProvider());
        $code = $auth->getCode((string)$user->getTwoFactorAuthenticationSecret(), $time + 30);

        $results = [];

        foreach ([$time, $time + 45, $time + 89] as $currentTime) {
            Yii::$container->set(TwoFactorAuthenticationValidator::class, [
                'currentTime' => $currentTime,
            ]);

            $form = Yii::$container->get(LoginForm::class, [], [
                'email' => 'f2a@domain.com',
                'password' => 'password',
                'code' => $code,
            ]);

            $results[] = $form->validate();
        }

        self::assertSame([true, false, false], $results);
        self::assertSame((int)floor(($time + 30) / 30), User::findOne($user->id)?->two_factor_timeslice);
    }
}
