<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\UpgradeController;
use Hirtz\Skeleton\Console\Controllers\UserController;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\console\ExitCode;

class UpgradePasswordsTest extends TestCase
{
    use UserFixtureTrait;

    public function testPasswordsMailsEveryUserWithoutAPassword(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['password_hash' => null]);

        $controller = new UpgradeControllerMock('upgrade', Yii::$app);
        $controller->interactive = false;
        $controller->actionPasswords();

        self::assertStringContainsString('Sent 1 password reset link', $controller->flushStdOutBuffer());
        self::assertNotNull($user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));

        $message = $this->mailer->getLastMessage();

        self::assertEquals($user->email, $this->mailer->getLastMessageTo());
        self::assertStringContainsString('/admin/account/reset', $this->mailer->getLastMessageBody());
    }

    /**
     * A failed send is reported and exits with an error; the next run picks the user up again, and skips the ones
     * whose link went out.
     */
    public function testAFailedSendIsReportedAndRetriedByTheNextRun(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['password_hash' => null]);

        $this->mailer->isFailing = true;

        $controller = new UpgradeControllerMock('upgrade', Yii::$app);
        $controller->interactive = false;

        self::assertSame(ExitCode::UNSPECIFIED_ERROR, $controller->actionPasswords());
        self::assertStringContainsString("Failed to send to $user->email", $controller->flushStdOutBuffer());
        self::assertNull($user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));

        $this->mailer->isFailing = false;

        self::assertSame(ExitCode::OK, $controller->actionPasswords());
        self::assertStringContainsString('Sent 1 password reset link', $controller->flushStdOutBuffer());

        self::assertSame(ExitCode::OK, $controller->actionPasswords());
        self::assertStringContainsString('Sent 0 password reset link', $controller->flushStdOutBuffer());
    }

    public function testADisabledUserIsNotMailed(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['password_hash' => null, 'status' => User::STATUS_DISABLED]);

        $controller = new UpgradeControllerMock('upgrade', Yii::$app);
        $controller->interactive = false;
        $controller->actionPasswords();

        self::assertFalse($this->mailer->hasMessages());
    }

    public function testTheLinkIsSentInTheUsersLanguage(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['password_hash' => null, 'language' => 'de']);

        $controller = new UpgradeControllerMock('upgrade', Yii::$app);
        $controller->interactive = false;
        $controller->actionPasswords();

        self::assertSame(
            Yii::$app->getI18n()->callback('de', fn (): string => Yii::t('skeleton', 'PASSWORD_RECOVER_RESET_YOUR_PASSWORD')),
            $this->mailer->getLastMessage()?->getSubject(),
        );

        self::assertSame('en-US', Yii::$app->language);
    }

    public function testPasswordsWithNothingToDo(): void
    {
        $controller = new UpgradeControllerMock('upgrade', Yii::$app);
        $controller->interactive = false;
        $controller->actionPasswords();

        self::assertStringContainsString('No users without a password found', $controller->flushStdOutBuffer());
        self::assertFalse($this->mailer->hasMessages());
    }

    public function testConsolePasswordSetsOne(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->updateAttributes(['password_hash' => null]);

        $controller = new UserControllerMock('user', Yii::$app);
        $controller->password = 'a-new-passphrase';
        $controller->actionPassword($user->email);

        self::assertStringContainsString('Password updated', $controller->flushStdOutBuffer());
        self::assertTrue(User::findOne($user->id)->validatePassword('a-new-passphrase'));
    }

    public function testConsolePasswordRejectsAShortOne(): void
    {
        $user = $this->getUserFromFixture('admin');

        $controller = new UserControllerMock('user', Yii::$app);
        $controller->password = 'short';
        $controller->actionPassword($user->email);

        self::assertStringContainsString('must be between 8 and 72 characters', $controller->flushStdOutBuffer());
        self::assertTrue(User::findOne($user->id)->validatePassword('password'));
    }
}

class UpgradeControllerMock extends UpgradeController
{
    use StdOutBufferControllerTrait;
}

class UserControllerMock extends UserController
{
    use StdOutBufferControllerTrait;
}
