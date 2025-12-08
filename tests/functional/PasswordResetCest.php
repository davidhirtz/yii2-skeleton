<?php

/**
 * @noinspection PhpUnused
 */

declare(strict_types=1);

namespace Hirtz\Skeleton\tests\functional;

use Hirtz\Skeleton\codeception\fixtures\UserFixtureTrait;
use Hirtz\Skeleton\codeception\functional\BaseCest;
use Hirtz\Skeleton\helpers\Html;
use Hirtz\Skeleton\models\forms\PasswordResetForm;
use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\modules\admin\Module;
use Hirtz\Skeleton\tests\support\FunctionalTester;
use Override;
use Yii;

class PasswordResetCest extends BaseCest
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
        $I->seeValidationError(Yii::t('skeleton', 'The password must match the new password.'));
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
        $form = PasswordResetForm::create();

        $I->submitForm('#password-reset-form', [
            Html::getInputName($form, 'newPassword') => $newPassword,
            Html::getInputName($form, 'repeatPassword') => $repeatPassword,
        ]);
    }
}
