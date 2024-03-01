<?php
/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordResetActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle($form->user->password_hash
    ? Yii::t('skeleton', 'Set New Password')
    : Yii::t('skeleton', 'Create Password'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your password could not be saved'),
]); ?>

<div class="container">
    <div class="centered">
        <?= Panel::widget([
            'title' => $this->title,
            'content' => PasswordResetActiveForm::widget([
                'model' => $form,
            ]),
        ]); ?>
    </div>
</div>