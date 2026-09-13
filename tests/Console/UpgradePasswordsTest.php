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

        self::assertEquals($user->email, key($message->getTo()));
        self::assertStringContainsString('/admin/account/reset', $message->getSymfonyEmail()->getHtmlBody());
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

    public string $password = '';

    protected function readPassword(): string
    {
        return $this->password;
    }
}
