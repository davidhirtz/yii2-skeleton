<?php
/**
 * Google Authenticator.
 * @see davidhirtz\yii2\skeleton\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 * @var yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\helpers\Html;

$this->setTitle(Yii::t('skeleton', 'Google Authenticator'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Login unsuccessful'),
]); ?>

<div class="container">
    <div class="centered">
        <?php Panel::begin(['title' => $this->title]); ?>
        <?php
        $af = ActiveForm::begin([
            'enableClientValidation' => false,
        ]);

        echo $af->field($form, 'code', ['icon' => 'qrcode', 'enableError' => false])->textInput([
            'autocomplete' => 'one-time-code',
            'autofocus' => !$form->hasErrors(),
        ]);
        ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('skeleton', 'Login'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?= Html::activeHiddenInput($form, 'email'); ?>
        <?= Html::activeHiddenInput($form, 'password'); ?>
        <?php $af::end(); ?>
        <?php Panel::end(); ?>
    </div>
</div>