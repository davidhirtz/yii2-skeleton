<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\functional;

use Hirtz\Skeleton\codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\codeception\functional\BaseCest;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\models\forms\PasswordRecoverForm;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\modules\admin\Module;
use Hirtz\Skeleton\tests\support\FunctionalTester;
use Override;
use Yii;
use yii\symfonymailer\Message;

class RecoverPasswordCest extends BaseCest
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
        $user = $I->grabUserFixture('admin');

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
        $form = PasswordRecoverForm::create();

        $I->submitForm('#password-recover-form', [
            Html::getInputName($form, 'email') => $email,
        ]);
    }
}
