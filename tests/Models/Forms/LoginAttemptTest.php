<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class LoginAttemptTest extends TestCase
{
    use UserFixtureTrait;

    public function testLockoutAfterFailedAttempts(): void
    {
        $webuser = Yii::$app->getUser();
        $webuser->loginAttemptLimit = 3;

        $email = $this->getUserFixtureData('owner')['email'];

        for ($i = 0; $i < 3; $i++) {
            self::assertFalse($this->createForm($email, 'wrong')->login());
            self::assertTrue($webuser->getIsGuest());
        }

        self::assertTrue($webuser->isLoginAttemptLimitReached($email));

        // The correct password is refused too, which is the whole point of a lockout
        $form = $this->createForm($email, 'password');

        self::assertFalse($form->login());
        self::assertEquals(Yii::t('skeleton', 'LOGIN_TOO_MANY_ATTEMPTS'), $form->getFirstError('email'));
        self::assertTrue($webuser->getIsGuest());
    }

    public function testFailedTwoFactorCodeCounts(): void
    {
        $webuser = Yii::$app->getUser();
        $webuser->loginAttemptLimit = 2;

        $email = $this->getUserFixtureData('admin')['email'];

        self::assertFalse($this->createForm($email, 'password', '000000')->login());
        self::assertFalse($this->createForm($email, 'password', '000000')->login());

        self::assertTrue($webuser->isLoginAttemptLimitReached($email));
    }

    public function testSuccessfulLoginClearsTheCounter(): void
    {
        $webuser = Yii::$app->getUser();
        $webuser->loginAttemptLimit = 3;

        $email = $this->getUserFixtureData('owner')['email'];

        self::assertFalse($this->createForm($email, 'wrong')->login());
        self::assertTrue($this->createForm($email, 'password')->login());

        self::assertFalse($webuser->isLoginAttemptLimitReached($email));
    }

    public function testBlankFormIsNotAnAttempt(): void
    {
        $webuser = Yii::$app->getUser();
        $webuser->loginAttemptLimit = 1;

        self::assertFalse($this->createForm('', '')->login());
        self::assertFalse($webuser->isLoginAttemptLimitReached(''));
    }

    private function createForm(string $email, string $password, ?string $code = null): LoginForm
    {
        return Yii::$container->get(LoginForm::class, [], [
            'email' => $email,
            'password' => $password,
            'code' => $code,
        ]);
    }
}
