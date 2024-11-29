<?php

declare(strict_types=1);

/**
 * @noinspection PhpUnused
 */

namespace davidhirtz\yii2\skeleton\tests\functional;

use davidhirtz\yii2\skeleton\codeception\fixtures\UserFixtureTrait;
use davidhirtz\yii2\skeleton\codeception\functional\BaseCest;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\Module;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
use Yii;
use yii\symfonymailer\Message;

class RecoverPasswordCest extends BaseCest
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

    public function checkPasswordRecoverLink(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/login");
        $I->click("a[href=\"/{$this->module->alias}/account/recover\"]");
        $I->see(Yii::t('skeleton', 'Recover Password'));
    }

    public function checkPasswordRecoverWithEmptyEmail(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/recover");
        $this->submitPasswordRecoverForm($I, '');
        $I->seeValidationError(Yii::t('skeleton', 'Email cannot be blank.'));
    }

    public function checkPasswordRecoverWithInvalidEmail(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/recover");
        $this->submitPasswordRecoverForm($I, 'invalid-email@domain.com');
        $I->seeValidationError(Yii::t('skeleton', 'Your email was not found.'));
    }

    public function checkPasswordRecoverWithValidEmail(FunctionalTester $I): void
    {
        $user = $I->grabFixture('user', 'admin');

        $I->amOnPage("/{$this->module->alias}/account/recover");
        $this->submitPasswordRecoverForm($I, $user->email);

        $user = User::findOne($user->id);
        $I->assertNotNull($user->password_reset_token);

        $I->amOnPage("/{$this->module->alias}/account/recover");
        $this->submitPasswordRecoverForm($I, $user->email);

        $I->seeValidationError(Yii::t('skeleton', 'We have just sent a link to reset your password to {email}. Please check your inbox!', [
            'email' => $user->email,
        ]));

        /** @var Message $message */
        $message = $I->grabLastSentEmail();
        $I->assertEquals(key($message->getTo()), $user->email);
        $I->assertStringContainsString($user->getPasswordResetUrl(), $message->getSymfonyEmail()->getHtmlBody());
    }

    protected function submitPasswordRecoverForm(FunctionalTester $I, string $email): void
    {
        $widget = Yii::createObject(PasswordRecoverActiveForm::class);

        $I->submitForm("#$widget->id", [
            Html::getInputName($widget->model, 'email') => $email,
        ]);
    }
}
