<?php
/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionResend()
 *
 * @var View $this
 * @var AccountResendConfirmForm $form
 * @var \yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Resend Account Confirmation'));
?>
<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your confirmation could not be resend'),
]); ?>
<div class="container">
    <div class="centered">
        <?php Panel::begin(['title' => $this->title]); ?>
        <p><?= Yii::t('skeleton', 'Enter your email address and we will send you another email to confirm your account.'); ?></p>
        <?php
        $af = ActiveForm::begin([
            'enableClientValidation' => false,
        ]);
        ?>
        <?= $af->field($form, 'email', [
            'inputOptions' => ['type' => 'email', 'autofocus' => !$form->hasErrors()],
            'icon' => 'envelope',
            'enableError' => false
        ]); ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('skeleton', 'Send Email'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php ActiveForm::end(); ?>
        <?php Panel::end(); ?>
    </div>
    <?php
    if (Yii::$app->getUser()->getIsGuest()) {
        ?>
        <div class="list-group">
            <a href="<?php echo Url::to(['login']); ?>" class="list-group-item list-group-item-action">
                <?= Icon::tag('sign-in-alt', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'Back to login'); ?>
            </a>
        </div>
        <?php
    }
    ?>
</div>