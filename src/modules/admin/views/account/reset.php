<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordResetActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;

$this->setTitle($form->user->password_hash
    ? Yii::t('skeleton', 'Set New Password')
    : Yii::t('skeleton', 'Create Password'));

echo ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'Your password could not be saved'));

echo Container::make()
    ->centered()
    ->html(Card::make()
        ->title($this->title)
        ->html(PasswordResetActiveForm::widget([
            'model' => $form,
        ])));
