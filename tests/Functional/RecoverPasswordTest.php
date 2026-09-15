<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

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

        $known = self::$crawler->html();

        self::assertTrue($this->mailer->hasMessages());
        self::assertSelectorNotExists('.form-error');

        $this->mailer->reset();

        $this->open('admin/account/recover');
        $this->submitPasswordRecoverForm('invalid-email@domain.com');

        // Same page, same redirect, no email — an unknown address is indistinguishable from a known one
        self::assertEquals($known, self::$crawler->html());
        self::assertCurrentUrlEquals('');
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

    protected function submitPasswordRecoverForm(string $email): void
    {
        $this->submit(values: $this->prefixFormValues(PasswordRecoverForm::instance(), [
            'email' => $email,
        ]));
    }
}
