<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models\Forms;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Models\Forms\PasswordResetForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
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
        self::assertFalse($this->getWebUser()->getIsGuest());
        self::assertEquals($user->id, $this->getWebUser()->getId());
    }

    public function testResetDoesNotLogInUserWithTwoFactorAuthentication(): void
    {
        $user = $this->getUserFromFixture('admin');
        self::assertTrue($user->hasTwoFactorAuthentication());

        $form = $this->createForm($user);

        self::assertNotFalse($form->reset());
        self::assertTrue($this->getWebUser()->getIsGuest(), 'The second factor must not be skipped');

        $user = User::findOne($user->id);
        self::assertNull($user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));
        self::assertTrue($user->validatePassword('new-password'));
    }

    public function testResetWithExpiredToken(): void
    {
        $user = $this->getUserFromFixture('owner');
        $form = $this->createForm($user);

        UserToken::updateAll(
            ['expires_at' => (new DateTime('-1 hour'))->format('Y-m-d H:i:s')],
            ['user_id' => $user->id]
        );

        self::assertFalse($form->reset());
        self::assertArrayHasKey('id', $form->getErrors());
        self::assertTrue($this->getWebUser()->getIsGuest());
    }

    public function testResetWithAnotherUsersToken(): void
    {
        $other = $this->getUserFromFixture('admin');
        $form = $this->createForm($this->getUserFromFixture('owner'));

        $form->code = $other->createVerificationToken();

        self::assertFalse($form->reset());
        self::assertArrayHasKey('id', $form->getErrors());
    }

    private function createForm(User $user): PasswordResetForm
    {
        return Yii::$container->get(PasswordResetForm::class, [], [
            'code' => $user->createPasswordResetToken(),
            'newPassword' => 'new-password',
            'repeatPassword' => 'new-password',
        ]);
    }
}
