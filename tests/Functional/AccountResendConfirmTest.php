<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\Functional;

use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Test\TestCase;
use Hirtz\Skeleton\Test\Traits\FunctionalTestTrait;
use Hirtz\Skeleton\Test\Traits\UserFixtureTrait;
use Override;
use Yii;

class AccountResendConfirmTest extends TestCase
{
    use FunctionalTestTrait;
    use UserFixtureTrait;

    protected string $uri;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->uri = "/$module->alias/account/resend";

        self::openUri($this->uri);
    }

    public function testResendConfirmWithEmptyEmail(): void
    {
        self::submitAccountResendConfirmForm('');
        self::assertAnyValidationErrorSame('Email cannot be blank.');
    }

    public function testResendConfirmWithInvalidEmail(): void
    {
        self::submitAccountResendConfirmForm('invalid-email@domain.com');
        self::assertAnyValidationErrorSame('Your email was not found.');
    }

    public function testResendConfirmAsConfirmedUser(): void
    {
        $user = $this->getUserFromFixture('owner');

        self::submitAccountResendConfirmForm($user->email);
        self::assertAnyValidationErrorSame('Your account was already confirmed!');
    }

    public function testResendConfirmWithValidEmail(): void
    {
        $user = $this->getUserFromFixture('admin');

        self::submitAccountResendConfirmForm($user->email);
        self::assertNotNull($user->verification_token);

        self::openUri($this->uri);
        self::submitAccountResendConfirmForm($user->email);

        $error = strtr('We have just sent a link to confirm your account to {email}. Please check your inbox!', [
            '{email}' => $user->email,
        ]);

        self::assertAnyValidationErrorSame($error);

        $email = $this->mailer->getLastMessage();

        self::assertEquals(key($email->getTo()), $user->email);
        self::assertStringContainsString($user->getEmailConfirmationUrl(), $email->getSymfonyEmail()->getHtmlBody());
    }

    public static function submitAccountResendConfirmForm(?string $email): void
    {
        $form = self::$crawler->filter('form')->form([
            Html::getInputName(AccountResendConfirmForm::instance(), 'email') => $email,
        ]);

        self::$crawler = self::$browser->submit($form);
    }
}
