<?php

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\unit\models\forms;

use Codeception\Test\Unit;
use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\models\forms\SignupForm;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\tests\support\UnitTester;
use Yii;
use yii\symfonymailer\Message;

/**
 * @property UnitTester $tester
 */
class SignupFormTest extends Unit
{
    use UserFixtureTrait;

    public function testSignupWithDisabledSignup()
    {
        $form = SignupForm::create();

        self::assertFalse($form->validate());
        self::assertContains('Sorry, signing up is currently disabled!', $form->getFirstErrors());
    }

    public function testSignupWithInvalidCredentials(): void
    {
        Yii::$app->getUser()->enableSignup = true;

        $form = TestSignupForm::create();
        $form->honeypot = 'test';

        self::assertFalse($form->validate());
        self::assertTrue($form->hasErrors('name'));
        self::assertTrue($form->hasErrors('email'));
        self::assertTrue($form->hasErrors('password'));
        self::assertTrue($form->hasErrors('terms'));
        self::assertTrue($form->hasErrors('token'));
        self::assertTrue($form->hasErrors('honeypot'));

        /** @var User $user */
        $user = $this->tester->grabFixture('user', 'admin');

        $form->name = 'Testname';
        $form->email = $user->email;
        $form->password = 'password';
        $form->token = $form->getSessionToken();
        $form->terms = true;
        $form->honeypot = null;

        self::assertFalse($form->validate());

        self::assertFalse($form->hasErrors('name'));
        self::assertFalse($form->hasErrors('password'));
        self::assertFalse($form->hasErrors('terms'));
        self::assertFalse($form->hasErrors('token'));
        self::assertFalse($form->hasErrors('honeypot'));

        self::assertEquals('This email address is already used by another user.', $form->getFirstError('email'));
    }

    public function testSignupWithValidCredentials(): void
    {
        Yii::$app->getUser()->enableSignup = true;

        $form = TestSignupForm::create();

        $form->name = 'Testname';
        $form->email = 'test-email@test.com';
        $form->password = 'password';
        $form->token = $form->getSessionToken();
        $form->terms = true;
        $form->honeypot = null;

        self::assertTrue($form->insert());
        self::assertFalse($form->user->getIsNewRecord());
        self::assertFalse(Yii::$app->getUser()->getIsGuest());

        /** @var Message $message */
        $message = $this->tester->grabLastSentEmail();
        self::assertStringContainsString($form->user->getEmailConfirmationUrl(), $message->getSymfonyEmail()->getHtmlBody());
    }

    public function testSignupWithIpSpamProtection(): void
    {
        $webuser = Yii::$app->getUser();
        $webuser->enableSignup = true;
        $webuser->ipAddress = '1.244.25.235';

        $form = TestSignupForm::create();

        $form->name = 'Testname';
        $form->email = 'test-email@test.com';
        $form->password = 'password';
        $form->token = $form->getSessionToken();
        $form->terms = true;
        $form->honeypot = null;

        self::assertTrue($form->insert());

        $form->name = 'Testname-2';
        $form->email = 'test-email2@test.com';
        $form->token = $form->getSessionToken();

        self::assertFalse($form->insert());
        self::assertContains('You have just created a new user account. Please wait a few minutes!', $form->getFirstErrors());
    }

    public function testSignupWithInvalidToken(): void
    {
        Yii::$app->getUser()->enableSignup = true;

        $form = SignupForm::create();

        $form->name = 'Testname';
        $form->email = 'test-email@test.com';
        $form->password = 'password';
        $form->token = $form->getSessionToken();
        $form->terms = true;
        $form->honeypot = null;

        self::assertFalse($form->insert());
        self::assertContains('Sign up could not be completed, please try again.', $form->getFirstErrors());
    }
}

class TestSignupForm extends SignupForm
{
    public const int SESSION_TOKEN_MIN_TIME = 0;
}
