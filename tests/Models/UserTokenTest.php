<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Db\DateTime;
use Hirtz\Skeleton\Console\Controllers\UserTokenController;
use Hirtz\Skeleton\Mcp\Mcp;
use Hirtz\Skeleton\Models\OAuthGrant;
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

    public function testAPasswordChangeRevokesTheMcpAccess(): void
    {
        $user = $this->getUserFromFixture('owner');
        $other = $this->getUserFromFixture('disabled');

        $grant = OAuthGrant::create();
        $grant->user_id = $user->id;
        $grant->client_id = 'https://claude.ai/oauth/mcp-oauth-client-metadata';
        $grant->resource = 'https://www.example.com/mcp';
        $grant->scope = OAuthGrant::SCOPE_WRITE;
        $grant->insert();

        UserToken::issue($user->id, UserToken::TYPE_MCP_REFRESH, 'refresh-token', 60, $grant->id);
        $token = Mcp::getComponent()->issueToken($user, true);
        $otherToken = Mcp::getComponent()->issueToken($other, true);
        $user->generateTwoFactorAuthenticationRecoveryCodes();
        $recoveryCodeCount = $user->getTwoFactorAuthenticationRecoveryCodeCount();

        $user->afterPasswordChange();

        self::assertNull(OAuthGrant::findOne($grant->id));
        self::assertNull(UserToken::find()->whereToken('refresh-token')->one());
        self::assertNull(UserToken::find()->whereToken($token)->one());
        self::assertNotNull(UserToken::find()->whereToken($otherToken)->one());
        self::assertSame($recoveryCodeCount, $user->getTwoFactorAuthenticationRecoveryCodeCount());
    }

    public function testExpiredTokenIsNotFound(): void
    {
        $user = $this->getUserFromFixture('owner');
        $token = $user->createPasswordResetToken();

        self::assertTrue($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $token));

        UserToken::updateAll(
            ['expires_at' => new DateTime('-1 second')],
            ['user_id' => $user->id]
        );

        self::assertFalse($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $token));
    }

    /**
     * A link sent to the old address proves nothing about the new one, and a reset link must not outlive the
     * password or the address it was sent for.
     */
    public function testAnEmailChangeDropsTheTokensSentBefore(): void
    {
        $user = $this->getUserFromFixture('owner');
        $verification = $user->createVerificationToken();
        $reset = $user->createPasswordResetToken();

        $user->email = 'zz-new-address@example.com';
        self::assertTrue($user->save(), print_r($user->getErrors(), true));

        self::assertFalse($this->findByToken(UserToken::TYPE_VERIFICATION, $verification));
        self::assertFalse($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $reset));
    }

    public function testAPasswordChangeDropsTheResetTokens(): void
    {
        $user = $this->getUserFromFixture('owner');
        $verification = $user->createVerificationToken();
        $reset = $user->createPasswordResetToken();

        $user->generatePasswordHash('a-new-password-zz');
        self::assertTrue($user->save(), print_r($user->getErrors(), true));

        self::assertTrue($this->findByToken(UserToken::TYPE_VERIFICATION, $verification));
        self::assertFalse($this->findByToken(UserToken::TYPE_PASSWORD_RESET, $reset));
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
            ['expires_at' => new DateTime('-1 hour')],
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
