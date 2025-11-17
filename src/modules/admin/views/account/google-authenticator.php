<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorLoginActiveForm;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\panels\Card;

$this->setTitle(Yii::t('skeleton', 'Google Authenticator'));

echo Container::make()
    ->content(ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'Login unsuccessful')));

echo Container::make()
    ->centered()
    ->content(Card::make()
        ->title($this->title)
        ->content(GoogleAuthenticatorLoginActiveForm::widget([
            'model' => $form,
        ])));
