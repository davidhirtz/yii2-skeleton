<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\GoogleAuthenticatorLoginActiveForm;

$this->setTitle(Yii::t('skeleton', 'Google Authenticator'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Login unsuccessful'),
]); ?>

<?= Container::make()
    ->html(Card::make()
        ->title($this->title)
        ->html(GoogleAuthenticatorLoginActiveForm::widget([
            'model' => $form,
        ])))
    ->centered()
    ->render(); ?>
