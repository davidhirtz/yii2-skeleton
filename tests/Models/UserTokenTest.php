<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use davidhirtz\yii2\datetime\DateTime;
use Hirtz\Skeleton\Console\Controllers\UserTokenController;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\StdOutBufferControllerTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class UserTokenTest extends TestCase
{
    use UserFixtureTrait;

    public function testOnlyTheHashIsStored(): void
    {
        $user = $this->getUserFromFixture('owner');
        $token = $user->createPasswordResetToken();

        $record = $user->getLatestToken(UserToken::TYPE_PASSWORD_RESET);

        self::assertNotNull($record);
        self::assertNotEquals($token, $record->token);
        self::assertEquals(UserToken::hash($token), $record->token);
        self::assertEquals(64, strlen($record->token));
    }

    public function testTokenIsScopedToItsType(): void
    {
        $user = $this->getUserFromFixture('owner');
        $token = $user->createVerificationToken();

        self::assertTrue($this->findByToken(UserToken::TYPE_VERIFICATION, $token));
        self::assertFalse($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $token));
    }

    public function testExpiredTokenIsNotFound(): void
    {
        $user = $this->getUserFromFixture('owner');
        $token = $user->createPasswordResetToken();

        self::assertTrue($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $token));

        UserToken::updateAll(
            ['expires_at' => (new DateTime('-1 second'))->format('Y-m-d H:i:s')],
            ['user_id' => $user->id]
        );

        self::assertFalse($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $token));
    }

    public function testDeletingAUserDeletesItsTokens(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->createPasswordResetToken();
        $user->generateTwoFactorAuthenticationRecoveryCodes();

        self::assertEquals(User::RECOVERY_CODE_COUNT + 1, UserToken::find()->whereUser($user->id)->count());

        $user->delete();

        self::assertEquals(0, UserToken::find()->whereUser($user->id)->count());
    }

    public function testClearCommandOnlyRemovesExpiredTokens(): void
    {
        $user = $this->getUserFromFixture('owner');

        $user->createPasswordResetToken();
        $user->generateTwoFactorAuthenticationRecoveryCodes();

        $expired = $user->createVerificationToken();

        UserToken::updateAll(
            ['expires_at' => (new DateTime('-1 hour'))->format('Y-m-d H:i:s')],
            ['type' => UserToken::TYPE_VERIFICATION]
        );

        $controller = new UserTokenControllerMock('user-token', Yii::$app);
        $controller->actionClear();

        self::assertStringContainsString('Deleted 1 expired tokens', $controller->flushStdOutBuffer());
        self::assertFalse($this->findByToken(UserToken::TYPE_VERIFICATION, $expired));

        // A recovery code has no expiry and is spent rather than collected
        self::assertEquals(
            User::RECOVERY_CODE_COUNT,
            UserToken::find()->whereUser($user->id)->whereType(UserToken::TYPE_RECOVERY_CODE)->count()
        );

        self::assertEquals(1, UserToken::find()->whereUser($user->id)->whereType(UserToken::TYPE_PASSWORD_RESET)->count());
    }

    private function findByToken(string $type, string $token): bool
    {
        return UserToken::find()
            ->whereType($type)
            ->whereToken($token)
            ->unexpired()
            ->exists();
    }
}

class UserTokenControllerMock extends UserTokenController
{
    use StdOutBufferControllerTrait;
}
