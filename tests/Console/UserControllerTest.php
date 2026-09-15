<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Console;

use Hirtz\Skeleton\Console\Controllers\UserController;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

/**
 * `user/password` is the way back into an installation whose only administrator lost their password and whose
 * mailer is not an option, so it has to work without a session, a token or a mail.
 */
class UserControllerTest extends TestCase
{
    use UserFixtureTrait;

    public function testPasswordIsSetAndTheSessionsAreEnded(): void
    {
        $user = $this->getUserFromFixture('owner');
        $previousHash = $user->password_hash;
        $previousAuthKey = $user->auth_key;

        $controller = $this->createController('a-brand-new-password');
        $controller->actionPassword($user->email);

        self::assertStringContainsString("Password updated for $user->email", $controller->flushStdOutBuffer());

        $user = User::findOne($user->id);

        self::assertNotSame($previousHash, $user->password_hash);
        self::assertNotSame($previousAuthKey, $user->auth_key);
        self::assertTrue($user->validatePassword('a-brand-new-password'));
    }

    public function testTheAddressIsMatchedWithoutRegardToCase(): void
    {
        $user = $this->getUserFromFixture('owner');

        $controller = $this->createController('a-brand-new-password');
        $controller->actionPassword(strtoupper($user->email));

        self::assertTrue(User::findOne($user->id)->validatePassword('a-brand-new-password'));
    }

    public function testAnUnknownAddressIsReported(): void
    {
        $controller = $this->createController('a-brand-new-password');
        $controller->actionPassword('nobody@domain.com');

        self::assertStringContainsString('No user found for nobody@domain.com', $controller->flushStdOutBuffer());
    }

    public function testAPasswordBelowTheMinimumLengthIsRefused(): void
    {
        $user = $this->getUserFromFixture('owner');
        $previousHash = $user->password_hash;

        $controller = $this->createController('short');
        $controller->actionPassword($user->email);

        self::assertStringContainsString('The password must be between', $controller->flushStdOutBuffer());
        self::assertSame($previousHash, User::findOne($user->id)->password_hash);
    }

    public function testAPasswordAboveTheMaximumLengthIsRefused(): void
    {
        $user = $this->getUserFromFixture('owner');
        $previousHash = $user->password_hash;

        $controller = $this->createController(str_repeat('a', $user->passwordMaxLength + 1));
        $controller->actionPassword($user->email);

        self::assertStringContainsString('The password must be between', $controller->flushStdOutBuffer());
        self::assertSame($previousHash, User::findOne($user->id)->password_hash);
    }

    public function testCreateInsertsTheAccount(): void
    {
        $controller = $this->createController('a-brand-new-password', [
            'Enter username:' => 'console',
            'Enter email address:' => 'console@domain.com',
        ]);

        $controller->actionCreate();

        self::assertStringContainsString('User account created.', $controller->flushStdOutBuffer());

        $user = User::find()
            ->andWhereEmail('console@domain.com')
            ->one();

        self::assertSame('console', $user?->name);
        self::assertTrue($user->validatePassword('a-brand-new-password'));
    }

    public function testCreateReportsTheErrorsOfADuplicateAddress(): void
    {
        $controller = $this->createController('a-brand-new-password', [
            'Enter username:' => 'console',
            'Enter email address:' => $this->getUserFromFixture('owner')->email,
        ]);

        $controller->actionCreate();

        $output = $controller->flushStdOutBuffer();

        self::assertStringNotContainsString('User account created.', $output);
        self::assertStringContainsString('Do you want to retry?', $output);
    }

    /**
     * @param array<string, string> $answers
     */
    private function createController(string $password, array $answers = []): TestUserController
    {
        $controller = new TestUserController('user', Yii::$app);
        $controller->password = $password;
        $controller->answers = $answers;

        return $controller;
    }
}

class TestUserController extends UserController
{
    use StdOutBufferControllerTrait;

    public string $password = '';
    /**
     * @var array<string, string>
     */
    public array $answers = [];

    #[Override]
    protected function readPassword(): string
    {
        $this->stdout('Enter password: ');
        return $this->password;
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    public function prompt($text, $options = []): string
    {
        return $this->answers[$text] ?? '';
    }
}
