<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;

class AccountResendConfirmTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->open('admin/account/resend');
    }

    public function testResendConfirmWithEmptyEmail(): void
    {
        $this->submitAccountResendConfirmForm();
        self::assertAnyValidationErrorSame('Email cannot be blank.');
    }

    public function testResendConfirmWithInvalidEmail(): void
    {
        $this->submitAccountResendConfirmForm('invalid-email@domain.com');
        self::assertAnyValidationErrorSame('Your email was not found.');
    }

    public function testResendConfirmAsConfirmedUser(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->submitAccountResendConfirmForm($user->email);
        self::assertAnyValidationErrorSame('Your account was already confirmed!');
    }

    public function testResendConfirmWithValidEmail(): void
    {
        $user = $this->getUserFromFixture('admin');

        $this->submitAccountResendConfirmForm($user->email);
        self::assertNotNull($user->verification_token);

        $this->open('admin/account/resend');
        $this->submitAccountResendConfirmForm($user->email);

        $error = strtr('We have just sent a link to confirm your account to {email}. Please check your inbox!', [
            '{email}' => $user->email,
        ]);

        self::assertAnyValidationErrorSame($error);

        $email = $this->mailer->getLastMessage();

        self::assertEquals(key($email->getTo()), $user->email);
        self::assertStringContainsString($user->getEmailConfirmationUrl(), $email->getSymfonyEmail()->getHtmlBody());
    }

    private function submitAccountResendConfirmForm(string $email = ''): void
    {
        $this->submit(values: $this->prefixFormValues(AccountResendConfirmForm::instance(), [
            'email' => $email,
        ]));
    }
}
