<?php
declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\html\Card;
use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordResetActiveForm;
use davidhirtz\yii2\skeleton\web\View;

$this->setTitle($form->user->password_hash
    ? Yii::t('skeleton', 'Set New Password')
    : Yii::t('skeleton', 'Create Password'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your password could not be saved'),
]); ?>

<?= Container::tag()
    ->content(Card::tag()
        ->title($this->title)
        ->body(PasswordResetActiveForm::widget([
            'model' => $form,
        ])))
    ->centered()
    ->render(); ?>
