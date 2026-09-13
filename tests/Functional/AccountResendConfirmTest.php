<?php

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

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

        // An address with no account reports the same success as one that gets the email, and gets no email
        self::assertSelectorNotExists('.form-error');
        self::assertFalse($this->mailer->hasMessages());
    }

    public function testResendConfirmAsConfirmedUser(): void
    {
        $user = $this->getUserFromFixture('owner');

        $this->submitAccountResendConfirmForm($user->email);

        self::assertSelectorNotExists('.form-error');
        self::assertFalse($this->mailer->hasMessages());
    }

    public function testResendConfirmNamesItsReasonWithoutEnumerationProtection(): void
    {
        Yii::$app->getUser()->enableUserEnumerationProtection = false;

        $this->submitAccountResendConfirmForm('invalid-email@domain.com');
        self::assertAnyValidationErrorSame('Your email was not found.');

        $this->open('admin/account/resend');
        $this->submitAccountResendConfirmForm($this->getUserFromFixture('owner')->email);
        self::assertAnyValidationErrorSame('Your account was already confirmed!');
    }

    public function testResendConfirmWithValidEmail(): void
    {
        $user = $this->getUserFromFixture('admin');

        $this->submitAccountResendConfirmForm($user->email);
        self::assertNotNull($user->verification_token);

        $email = $this->mailer->getLastMessage();

        self::assertEquals(key($email->getTo()), $user->email);
        self::assertStringContainsString($user->getEmailConfirmationUrl(), $email->getSymfonyEmail()->getHtmlBody());

        // The spam protection must not answer differently either
        $this->mailer->reset();

        $this->open('admin/account/resend');
        $this->submitAccountResendConfirmForm($user->email);

        self::assertFalse($this->mailer->hasMessages());
        self::assertSelectorNotExists('.form-error');
    }

    private function submitAccountResendConfirmForm(string $email = ''): void
    {
        $this->submit(values: $this->prefixFormValues(AccountResendConfirmForm::instance(), [
            'email' => $email,
        ]));
    }
}
