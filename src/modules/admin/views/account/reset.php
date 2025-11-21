<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordResetActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\panels\Card;

$this->title($form->user->password_hash
    ? Yii::t('skeleton', 'Set New Password')
    : Yii::t('skeleton', 'Create Password'));

echo Container::make()
    ->content(ErrorSummary::make()->models($form)
        ->title(Yii::t('skeleton', 'Your password could not be saved')));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(PasswordResetActiveForm::widget([
            'model' => $form,
        ])));
