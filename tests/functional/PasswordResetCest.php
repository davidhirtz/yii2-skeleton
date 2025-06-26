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
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordResetActiveForm;
use davidhirtz\yii2\skeleton\tests\support\FunctionalTester;
use Yii;

class PasswordResetCest extends BaseCest
{
    use UserFixtureTrait;

    private ?Module $module = null;

    #[\Override]
    public function _before(): void
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('admin');
        $this->module = $module;

        parent::_before();
    }

    public function checkPasswordResetWithMissingUrlParameters(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/reset");
        $I->seeResponseCodeIsClientError();
    }

    public function checkPasswordResetWithInvalidUrlParameters(FunctionalTester $I): void
    {
        $I->amOnPage("/{$this->module->alias}/account/reset?email=invalid%40domain.com&code=invalid-code");
        $I->seeCurrentUrlEquals('/');
    }

    public function checkPasswordResetWithWrongInputs(FunctionalTester $I): void
    {
        $user = $this->getUser();
        $I->amOnPage($user->getPasswordResetUrl());

        $this->submitPasswordResetForm($I, 'new-password', 'wrong-repeat-password');
        $I->seeValidationError(Yii::t('skeleton', 'Your password could not be saved'));
    }

    public function checkPasswordResetWithCorrectInputs(FunctionalTester $I): void
    {
        $user = $this->getUser();
        $I->amOnPage($user->getPasswordResetUrl());

        $this->submitPasswordResetForm($I, 'new-password', 'new-password');
        $I->seeCurrentUrlEquals('/');
        $I->amLoggedInAs($user);
    }

    private function getUser(): User
    {
        $user = User::findOne(1);
        $user->generatePasswordResetToken();
        $user->update();

        return $user;
    }

    private function submitPasswordResetForm(FunctionalTester $I, string $newPassword, string $repeatPassword): void
    {
        $widget = Yii::createObject(PasswordResetActiveForm::class);

        $I->submitForm("#$widget->id", [
            Html::getInputName($widget->model, 'newPassword') => $newPassword,
            Html::getInputName($widget->model, 'repeatPassword') => $repeatPassword,
        ]);
    }
}
