<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountResendConfirmActiveForm;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
use Yii;
use yii\symfonymailer\Message;

class AccountResendConfirmCest extends BaseCest
{
    use UserFixtureTrait;

    private ?Module $module = null;

    public function _before(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->module = $module;

        parent::_before();
    }

    public function checkResendConfirmWithEmptyEmail(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/resend");
        $this->submitAccountResendConfirmForm($I, '');
        $I->seeValidationError(Yii::t('skeleton', 'Email cannot be blank.'));
    }

    public function checkResendConfirmWithInvalidEmail(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/resend");
        $this->submitAccountResendConfirmForm($I, 'invalid-email@domain.com');
        $I->seeValidationError(Yii::t('skeleton', 'Your email was not found.'));
    }

    public function checkResendConfirmAsConfirmedUser(FunctionalTester $I): void
    {
        /** @var User $user */
        $user = $I->grabFixture('user', 'owner');

        $I->amOnPage("/{$this->module->alias}/account/resend");
        $this->submitAccountResendConfirmForm($I, $user->email);

        $I->seeValidationError(Yii::t('skeleton', 'Your account was already confirmed!'));
    }

    public function checkResendConfirmWithValidEmail(FunctionalTester $I): void
    {
        /** @var User $user */
        $user = $I->grabFixture('user', 'admin');

        $I->amOnPage("/{$this->module->alias}/account/resend");
        $this->submitAccountResendConfirmForm($I, $user->email);

        $user = User::findOne($user->id);
        $I->assertNotNull($user->verification_token);

        $I->amOnPage("/{$this->module->alias}/account/resend");
        $this->submitAccountResendConfirmForm($I, $user->email);

        $error = Yii::t('skeleton', 'We have just sent a link to confirm your account to {email}. Please check your inbox!', [
            'email' => $user->email,
        ]);

        $I->seeValidationError($error);

        /** @var Message $email */
        $email = $I->grabLastSentEmail();
        $I->assertEquals(key($email->getTo()), $user->email);
        $I->assertStringContainsString($user->getEmailConfirmationUrl(), $email->getSymfonyEmail()->getHtmlBody());
    }

    protected function submitAccountResendConfirmForm(FunctionalTester $I, string $email): void
    {
        $widget = Yii::createObject(AccountResendConfirmActiveForm::class);

        $I->submitForm("#$widget->id", [
            Html::getInputName($widget->model, 'email') => $email,
        ]);
    }
}
