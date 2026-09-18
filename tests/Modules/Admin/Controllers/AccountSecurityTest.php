<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Modules\Admin\Controllers;

use Hirtz\Skeleton\Models\Forms\TwoFactorAuthenticatorForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Controllers\AccountController;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Hirtz\Skeleton\Validators\TwoFactorAuthenticationValidator;
use RobThree\Auth\Providers\Qr\QRServerProvider;
use RobThree\Auth\TwoFactorAuth;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\MethodNotAllowedHttpException;
use yii\web\Response;

class AccountSecurityTest extends TestCase
{
    use UserFixtureTrait;

    public function testTheAuthenticatorIsEnabledWithACodeFromTheSessionSecret(): void
    {
        $user = $this->login('owner');
        $secret = TwoFactorAuthenticatorForm::create(['user' => $user])->getSecret();

        $this->post('admin/account/enable-authenticator', [
            'GoogleAuthenticator' => ['code' => $this->getCode($secret)],
        ]);

        $user = User::findOne($user->id);

        self::assertTrue($user->hasTwoFactorAuthentication());
        self::assertSame($secret, $user->getTwoFactorAuthenticationSecret());
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));

        // the codes are readable exactly once, on the page the redirect lands on
        $codes = $this->getWebSession()->getFlash(AccountController::RECOVERY_CODES_FLASH);

        self::assertCount(User::RECOVERY_CODE_COUNT, $codes);
    }

    public function testTheAuthenticatorIsNotEnabledWithAWrongCode(): void
    {
        $user = $this->login('owner');
        TwoFactorAuthenticatorForm::create(['user' => $user])->getSecret();

        $this->post('admin/account/enable-authenticator', [
            'GoogleAuthenticator' => ['code' => '000000'],
        ]);

        self::assertFalse(User::findOne($user->id)->hasTwoFactorAuthentication());
        self::assertNotEmpty($this->getWebSession()->getFlash('danger'));
        self::assertEmpty($this->getWebSession()->getFlash(AccountController::RECOVERY_CODES_FLASH));
    }

    public function testTheAuthenticatorIsDisabledWithACode(): void
    {
        $user = $this->login('admin');

        $this->post('admin/account/disable-authenticator', [
            'GoogleAuthenticator' => ['code' => $this->getCode($user->getTwoFactorAuthenticationSecret())],
        ]);

        self::assertFalse(User::findOne($user->id)->hasTwoFactorAuthentication());
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    /**
     * A lost device is recoverable without an administrator, so a recovery code turns the second factor off too.
     */
    public function testTheAuthenticatorIsDisabledWithARecoveryCode(): void
    {
        $user = $this->login('admin');

        $codes = $user->generateTwoFactorAuthenticationRecoveryCodes();
        $user->update();

        $this->post('admin/account/disable-authenticator', [
            'GoogleAuthenticator' => ['code' => $codes[0]],
        ]);

        self::assertFalse(User::findOne($user->id)->hasTwoFactorAuthentication());
    }

    public function testTheAuthenticatorIsNotDisabledWithAWrongCode(): void
    {
        $user = $this->login('admin');

        $this->post('admin/account/disable-authenticator', [
            'GoogleAuthenticator' => ['code' => '000000'],
        ]);

        self::assertTrue(User::findOne($user->id)->hasTwoFactorAuthentication());
        self::assertNotEmpty($this->getWebSession()->getFlash('danger'));
    }

    public function testTheSecurityPageIsGoneWhileTwoFactorAuthenticationIsOff(): void
    {
        $this->login('owner');
        $this->getWebUser()->enableTwoFactorAuthentication = false;

        $this->expectException(ForbiddenHttpException::class);
        Yii::$app->runAction('admin/account/security');
    }

    public function testTheAuthenticatorActionsRefuseAGetRequest(): void
    {
        $this->login('owner');

        $this->expectException(MethodNotAllowedHttpException::class);
        Yii::$app->runAction('admin/account/enable-authenticator');
    }

    public function testLogoutOtherSessionsReportsWhatItEnded(): void
    {
        $this->login('owner');

        $response = $this->post('admin/account/logout-other-sessions');

        self::assertInstanceOf(Response::class, $response);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testTheTimezoneIsSaved(): void
    {
        $user = $this->login('owner');

        $this->post('admin/account/timezone', ['timezone' => 'Europe/Berlin']);

        self::assertSame('Europe/Berlin', User::findOne($user->id)->timezone);
        self::assertNotEmpty($this->getWebSession()->getFlash('success'));
    }

    public function testAnInvalidTimezoneIsRefused(): void
    {
        $user = $this->login('owner');

        $this->post('admin/account/timezone', ['timezone' => 'Mars/Olympus']);

        self::assertNull(User::findOne($user->id)->timezone);
        self::assertNotEmpty($this->getWebSession()->getFlash('danger'));
    }

    /**
     * The modal used to set the value through `hx-vars`, which htmx 4 dropped, so the button posted an empty body —
     * and an empty value is skipped by every validator, so the stored timezone was cleared and the flash still read
     * as a success (monorepo issue #191).
     */
    public function testAPostCarryingNoTimezoneIsRefused(): void
    {
        $user = $this->login('owner');
        $user->updateAttributes(['timezone' => 'Europe/Berlin']);

        $this->expectException(BadRequestHttpException::class);

        try {
            $this->post('admin/account/timezone');
        } finally {
            self::assertSame('Europe/Berlin', User::findOne($user->id)->timezone);
        }
    }

    public function testTheTimezoneRedirectsWhereItWasAskedTo(): void
    {
        $this->login('owner');

        $response = $this->post('admin/account/timezone', ['timezone' => 'UTC'], ['redirect' => '/somewhere']);

        self::assertInstanceOf(Response::class, $response);
        self::assertStringEndsWith('/somewhere', (string)$response->getHeaders()->get('location'));
    }

    public function testAGuestIsSentToTheLoginPage(): void
    {
        self::assertNull(Yii::$app->runAction('admin/account/security'));

        $location = $this->getWebResponse()->getHeaders()->get('location');

        self::assertStringContainsString('account/login', (string)$location);
    }

    private function getCode(string $secret): string
    {
        $validator = Yii::createObject(TwoFactorAuthenticationValidator::class);
        $auth = new TwoFactorAuth(new QRServerProvider(), digits: $validator->length, period: $validator->period);

        return $auth->getCode($secret);
    }

    /**
     * @param array<string, mixed> $bodyParams
     * @param array<string, mixed> $params
     */
    private function post(string $route, array $bodyParams = [], array $params = []): mixed
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $request = $this->getWebRequest();
        $request->setBodyParams([...$bodyParams, $request->csrfParam => $request->getCsrfToken()]);

        return Yii::$app->runAction($route, $params);
    }

    private function login(string $fixtureKey): User
    {
        $user = $this->getUserFromFixture($fixtureKey);
        $this->getWebUser()->setIdentity($user);

        return $user;
    }
}
