<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\PasswordRecoverForm;
use Hirtz\Skeleton\Models\User;
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

    public function testPasswordRecoverWithInvalidEmail(): void
    {
        $this->open("admin/account/recover");
        $this->submitPasswordRecoverForm('invalid-email@domain.com');
        self::assertAnyValidationErrorSame('Your email was not found.');
    }

    public function testPasswordRecoverWithValidEmail(): void
    {
        $user = $this->getUserFromFixture('admin');

        $this->open("admin/account/recover");
        $this->submitPasswordRecoverForm($user->email);

        $user = User::findOne($user->id);
        self::assertNotNull($user->password_reset_token);

        $this->open("admin/account/recover");
        $this->submitPasswordRecoverForm($user->email);

        self::assertAnyValidationErrorSame(strtr('We have just sent a link to reset your password to {email}. Please check your inbox!', [
            '{email}' => $user->email,
        ]));

        $message = $this->mailer->getLastMessage();

        self::assertEquals(key($message->getTo()), $user->email);
        self::assertStringContainsString($user->getPasswordResetUrl(), $message->getSymfonyEmail()->getHtmlBody());
    }

    protected function submitPasswordRecoverForm(string $email): void
    {
        $this->submit(values: $this->prefixFormValues(PasswordRecoverForm::instance(), [
            'email' => $email,
        ]));
    }
}
