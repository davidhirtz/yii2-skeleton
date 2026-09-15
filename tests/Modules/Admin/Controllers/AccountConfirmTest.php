<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserLogin;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Yii;
use yii\web\Response;

/**
 * Covers `AccountController::actionConfirm()`, which is the one flow that logs a user in without a password.
 */
class AccountConfirmTest extends TestCase
{
    use UserFixtureTrait;

    public function testAValidCodeConfirmsTheAddressAndLogsTheUserIn(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->setTwoFactorAuthenticationSecret(null);
        $user->update();

        $response = Yii::$app->runAction('admin/account/confirm', [
            'code' => $user->createVerificationToken(),
        ]);

        self::assertInstanceOf(Response::class, $response);
        self::assertFalse(User::findOne($user->id)->isUnconfirmed());

        self::assertSame($user->id, $this->getWebUser()->getId());
        self::assertSame(UserLogin::TYPE_CONFIRM_EMAIL, $this->getWebUser()->loginType);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    /**
     * The confirmation link is the second factor's blind spot: it arrives by email and carries no code, so it
     * confirms the address and stops there.
     */
    public function testAUserWithASecondFactorIsNotLoggedIn(): void
    {
        $user = $this->getUserFromFixture('admin');

        Yii::$app->runAction('admin/account/confirm', [
            'code' => $user->createVerificationToken(),
        ]);

        self::assertFalse(User::findOne($user->id)->isUnconfirmed());
        self::assertTrue($this->getWebUser()->getIsGuest());
    }

    public function testADisabledUserIsNotLoggedIn(): void
    {
        $user = $this->getUserFromFixture('disabled');
        $user->updateAttributes(['email_confirmed_at' => null]);

        Yii::$app->runAction('admin/account/confirm', [
            'code' => $user->createVerificationToken(),
        ]);

        self::assertFalse(User::findOne($user->id)->isUnconfirmed());
        self::assertTrue($this->getWebUser()->getIsGuest());
    }

    public function testAUserWhoIsAlreadyLoggedInIsNotSwitched(): void
    {
        $user = $this->getUserFromFixture('admin');
        $user->setTwoFactorAuthenticationSecret(null);
        $user->update();

        $owner = $this->getUserFromFixture('owner');
        $this->getWebUser()->setIdentity($owner);

        Yii::$app->runAction('admin/account/confirm', [
            'code' => $user->createVerificationToken(),
        ]);

        self::assertSame($owner->id, $this->getWebUser()->getId());
    }

    public function testAnInvalidCodeIsReportedAndConfirmsNothing(): void
    {
        $user = $this->getUserFromFixture('admin');

        Yii::$app->runAction('admin/account/confirm', ['code' => str_repeat('a', 32)]);

        self::assertTrue(User::findOne($user->id)->isUnconfirmed());
        self::assertTrue($this->getWebUser()->getIsGuest());
        self::assertNotEmpty($this->getWebSession()->getFlash('danger'));
    }

    public function testTheSignupTokenIsHandedOutAsJson(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([$request->csrfParam => $request->getCsrfToken()]);

        $data = Yii::$app->runAction('admin/account/token');

        self::assertIsArray($data);
        self::assertNotEmpty($data['csrf']);
        self::assertNotEmpty($data['token']);
    }
}
