<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Forms\LoginForm;
use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Models\UserToken;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;

class RecoverPasswordTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    public function testPasswordRecoverLink(): void
    {
        $this->open('admin/account/login');
        $this->click("a[href=\"/admin/account/recover\"]");
        self::assertCurrentUrlEquals('/admin/account/recover');
    }

    public function testPasswordRecoverWithEmptyEmail(): void
    {
        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm('');
        self::assertAnyValidationErrorSame('Email cannot be blank.');
    }

    public function testPasswordRecoverDoesNotSayWhichAddressesExist(): void
    {
        $user = $this->getUserFromFixture('admin');

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm($user->email);

        $known = $this->getNormalizedLoginPage($user->email);

        self::assertTrue($this->mailer->hasMessages());
        self::assertSelectorNotExists('.form-error');

        $this->mailer->reset();

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm('invalid-email@domain.com');

        // Same page, same redirect, no email — an unknown address is indistinguishable from a known one
        self::assertEquals($known, $this->getNormalizedLoginPage('invalid-email@domain.com'));
        self::assertCurrentUrlEquals('/admin/account/login');
        self::assertFalse($this->mailer->hasMessages());
    }

    public function testPasswordRecoverWithInvalidEmailWithoutEnumerationProtection(): void
    {
        $this->getWebUser()->enableUserEnumerationProtection = false;

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm('invalid-email@domain.com');

        self::assertAnyValidationErrorSame('Your email was not found.');
    }

    public function testPasswordRecoverWithValidEmail(): void
    {
        $user = $this->getUserFromFixture('admin');

        $this->open("admin/account/recover");
        $this->submitPasswordRecoverForm($user->email);

        self::assertCurrentUrlEquals('/admin/account/login');
        self::assertInputValueSame(Html::getInputName(LoginForm::instance(), 'email'), $user->email);

        $user = User::findOne($user->id);
        self::assertNotNull($user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));

        $message = $this->mailer->getLastMessage();

        self::assertEquals($this->mailer->getLastMessageTo(), $user->email);
        self::assertStringContainsString('/admin/account/reset', $this->mailer->getLastMessageBody());

        // The spam protection must not answer differently either: a second request reports the same success
        $this->mailer->reset();

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm($user->email);

        self::assertFalse($this->mailer->hasMessages());
        self::assertSelectorNotExists('.form-error');
    }

    public function testAFailedEmailReportsTheSameSuccessAndLetsTheRetryThrough(): void
    {
        $user = $this->getUserFromFixture('admin');
        $this->mailer->isFailing = true;

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm($user->email);

        self::assertSelectorNotExists('.form-error');
        self::assertNull($user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));

        $this->mailer->isFailing = false;

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm($user->email);

        self::assertSame($user->email, $this->mailer->getLastMessageTo());
    }

    public function testAFailedEmailIsNamedWithoutEnumerationProtection(): void
    {
        $user = $this->getUserFromFixture('admin');

        $this->getWebUser()->enableUserEnumerationProtection = false;
        $this->mailer->isFailing = true;

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm($user->email);

        self::assertAnyValidationErrorSame("The email to $user->email could not be sent. Please try again later.");
        self::assertNull($user->getLatestToken(UserToken::TYPE_PASSWORD_RESET));
    }

    private function getNormalizedLoginPage(string $email): string
    {
        $html = self::$crawler->filter('main')->html() . self::$crawler->filter('flash-alert')->last()->html();
        $html = str_replace($email, '{email}', $html);

        return preg_replace('/name="_csrf" value="[^"]+"/', '', $html) ?? self::fail('Invalid HTML.');
    }

    protected function submitPasswordRecoverForm(string $email): void
    {
        $this->submit(values: $this->prefixFormValues(PasswordRecoverForm::instance(), [
            'email' => $email,
        ]));
    }
}
