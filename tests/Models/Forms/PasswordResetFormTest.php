<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use Hirtz\Skeleton\Models\Forms\PasswordResetForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class PasswordResetFormTest extends TestCase
{
    use UserFixtureTrait;

    public function testResetLogsInUserWithoutTwoFactorAuthentication(): void
    {
        $user = $this->getUserFromFixture('owner');
        $form = $this->createForm($user);

        self::assertNotFalse($form->reset());
        self::assertFalse(Yii::$app->getUser()->getIsGuest());
        self::assertEquals($user->id, Yii::$app->getUser()->getId());
    }

    public function testResetDoesNotLogInUserWithTwoFactorAuthentication(): void
    {
        $user = $this->getUserFromFixture('admin');
        self::assertNotEmpty($user->google_2fa_secret);

        $form = $this->createForm($user);

        self::assertNotFalse($form->reset());
        self::assertTrue(Yii::$app->getUser()->getIsGuest(), 'The second factor must not be skipped');

        $user = User::findOne($user->id);
        self::assertNull($user->password_reset_token);
        self::assertTrue($user->validatePassword('new-password'));
    }

    private function createForm(User $user): PasswordResetForm
    {
        $user->generatePasswordResetToken();
        $user->update();

        return Yii::$container->get(PasswordResetForm::class, [], [
            'email' => $user->email,
            'code' => $user->password_reset_token,
            'newPassword' => 'new-password',
            'repeatPassword' => 'new-password',
        ]);
    }
}
