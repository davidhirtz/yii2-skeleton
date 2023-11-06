<?php
/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionReset()
 *
 * @var View $this
 * @var PasswordResetForm $form
 * @var \yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\PasswordResetForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;

$title = $form->user->password_hash ? Yii::t('skeleton', 'Set New Password') : Yii::t('skeleton', 'Create Password');
$description = $form->user->password_hash ? Yii::t('skeleton', 'Please enter a new password below to update your account.') : Yii::t('skeleton', 'Please enter a password below to complete your account.');
$this->setTitle($title);
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your password could not be saved'),
]); ?>

<div class="container">
    <div class="centered">
        <?php Panel::begin(['title' => $this->title]); ?>
        <p><?= $description; ?></p>
        <?php
        $af = ActiveForm::begin([
            'enableClientValidation' => false,
        ]);

        echo $af->field($form, 'email', ['inputOptions' => ['readonly' => true], 'icon' => 'envelope']);
        echo $af->field($form, 'newPassword', ['icon' => 'key'])->passwordInput(['autofocus' => !$form->hasErrors()]);
        echo $af->field($form, 'repeatPassword', ['icon' => 'key'])->passwordInput();
        ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('skeleton', 'Save New Password'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php $af::end(); ?>
        <?php Panel::end(); ?>
    </div>
</div>