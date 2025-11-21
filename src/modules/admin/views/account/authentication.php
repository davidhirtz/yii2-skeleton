<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\TwoFactorAuthenticatorLoginActiveForm;
use davidhirtz\yii2\skeleton\widgets\panels\Card;

$this->title(Yii::t('skeleton', 'Two-Factor Authentication'));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(TwoFactorAuthenticatorLoginActiveForm::make()
            ->model($form)));
