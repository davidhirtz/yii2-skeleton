<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Skeleton\Tests\functional;

use Hirtz\Skeleton\Codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\Codeception\functional\BaseCest;
use Hirtz\Skeleton\Helpers\Html;
use Hirtz\Skeleton\Models\Forms\AccountResendConfirmForm;
use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Module;
use Hirtz\Skeleton\Tests\support\FunctionalTester;
use Override;
use Yii;
use yii\symfonymailer\Message;

class AccountResendConfirmCest extends BaseCest
{
    use UserFixtureTrait;

    private ?Module $module = null;

    #[Override]
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
        $user = $I->grabUserFixture();

        $I->amOnPage("/{$this->module->alias}/account/resend");
        $this->submitAccountResendConfirmForm($I, $user->email);

        $I->seeValidationError(Yii::t('skeleton', 'Your account was already confirmed!'));
    }

    public function checkResendConfirmWithValidEmail(FunctionalTester $I): void
    {
        $user = $I->grabUserFixture('admin');

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
        $model = AccountResendConfirmForm::create();

        $I->submitForm("#resend-form", [
            Html::getInputName($model, 'email') => $email,
        ]);
    }
}
