<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Models;

use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\Forms\TwoFactorAuthenticatorForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;

class UserTwoFactorAuthenticationTest extends TestCase
{
    use UserFixtureTrait;

    public function testSecretIsEncryptedInTheColumn(): void
    {
        $user = $this->getUserFromFixture('owner');
        $user->setTwoFactorAuthenticationSecret('AX7CR435GC575V4C');
        $user->update();

        $user = User::findOne($user->id);

        self::assertStringNotContainsString('AX7CR435GC575V4C', (string)$user->google_2fa_secret);
        self::assertEquals('AX7CR435GC575V4C', $user->getTwoFactorAuthenticationSecret());
        self::assertTrue($user->hasTwoFactorAuthentication());
    }

    public function testPlaintextSecretIsStillReadable(): void
    {
        // Written before the column was encrypted, as the fixture still is
        $user = $this->getUserFromFixture('admin');

        self::assertEquals('AX7CR435GC575V4C', $user->google_2fa_secret);
        self::assertEquals('AX7CR435GC575V4C', $user->getTwoFactorAuthenticationSecret());
    }

    public function testRecoveryCodesAreOnlyStoredHashed(): void
    {
        $user = $this->getUserFromFixture('admin');
        $codes = $user->generateTwoFactorAuthenticationRecoveryCodes();
        $user->update();

        self::assertCount(User::RECOVERY_CODE_COUNT, $codes);
        self::assertCount(User::RECOVERY_CODE_COUNT, array_unique($codes));

        $user = User::findOne($user->id);

        $stored = UserToken::find()
            ->select(['token'])
            ->whereUser($user->id)
            ->whereType(UserToken::TYPE_RECOVERY_CODE)
            ->column();

        self::assertCount(User::RECOVERY_CODE_COUNT, $stored);

        foreach ($codes as $code) {
            self::assertSame(User::RECOVERY_CODE_LENGTH, strlen($code));
            self::assertNotContains($code, $stored);
        }
    }

    public function testRecoveryCodeIsGoodOnce(): void
    {
        $user = $this->getUserFromFixture('admin');
        $codes = $user->generateTwoFactorAuthenticationRecoveryCodes();
        $user->update();

        $user = User::findOne($user->id);

        // Typed in either case, with or without the whitespace a copy tends to bring along
        self::assertTrue($user->validateTwoFactorAuthenticationRecoveryCode(' ' . strtolower($codes[0]) . ' '));
        self::assertFalse($user->validateTwoFactorAuthenticationRecoveryCode($codes[0]));
        self::assertFalse($user->validateTwoFactorAuthenticationRecoveryCode('NOTACODE12'));

        self::assertEquals(
            User::RECOVERY_CODE_COUNT - 1,
            User::findOne($user->id)->getTwoFactorAuthenticationRecoveryCodeCount()
        );
    }

    public function testLoginWithARecoveryCode(): void
    {
        $user = $this->getUserFromFixture('admin');
        $codes = $user->generateTwoFactorAuthenticationRecoveryCodes();
        $user->update();

        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => $user->email,
            'password' => 'password',
            'code' => $codes[0],
        ]);

        self::assertTrue($form->login());
        self::assertFalse(Yii::$app->getUser()->getIsGuest());

        self::assertEquals(
            User::RECOVERY_CODE_COUNT - 1,
            User::findOne($user->id)->getTwoFactorAuthenticationRecoveryCodeCount()
        );
    }

    public function testLoginWithAWrongRecoveryCode(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->generateTwoFactorAuthenticationRecoveryCodes();
        $user->update();

        $form = Yii::$container->get(LoginForm::class, [], [
            'email' => $user->email,
            'password' => 'password',
            'code' => 'NOTACODE12',
        ]);

        self::assertFalse($form->login());
        self::assertArrayHasKey('code', $form->getErrors());
        self::assertTrue(Yii::$app->getUser()->getIsGuest());
    }

    public function testRecoveryCodeDisablesTwoFactorAuthentication(): void
    {
        $user = $this->getUserFromFixture('admin');
        $codes = $user->generateTwoFactorAuthenticationRecoveryCodes();
        $user->update();

        $form = TwoFactorAuthenticatorForm::create(['user' => $user]);
        $form->code = $codes[0];

        self::assertNotFalse($form->delete());
        self::assertFalse(User::findOne($user->id)->hasTwoFactorAuthentication());
    }

    public function testEnablingTwoFactorAuthenticationIssuesRecoveryCodes(): void
    {
        $user = $this->getUserFromFixture('owner');
        $form = TwoFactorAuthenticatorForm::create(['user' => $user]);

        $auth = new \RobThree\Auth\TwoFactorAuth(new \RobThree\Auth\Providers\Qr\QRServerProvider());
        $form->code = $auth->getCode($form->getSecret());

        self::assertTrue($form->save());
        self::assertCount(User::RECOVERY_CODE_COUNT, $form->recoveryCodes);

        $user = User::findOne($user->id);

        self::assertTrue($user->hasTwoFactorAuthentication());
        self::assertEquals(User::RECOVERY_CODE_COUNT, $user->getTwoFactorAuthenticationRecoveryCodeCount());
    }
}
