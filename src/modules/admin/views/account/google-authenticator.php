<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\modules\admin\widgets\ErrorSummary;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorLoginActiveForm;

$this->setTitle(Yii::t('skeleton', 'Google Authenticator'));

echo ErrorSummary::forModel($form)
    ->title(Yii::t('skeleton', 'Login unsuccessful'));

echo Container::make()
    ->centered()
    ->html(Card::make()
        ->title($this->title)
        ->html(GoogleAuthenticatorLoginActiveForm::widget([
            'model' => $form,
        ])));
