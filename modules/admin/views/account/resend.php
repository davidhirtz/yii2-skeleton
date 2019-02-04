<?php
/**
 * Resend confirmation form.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionResend()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm $form
 * @var \yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use rmrevin\yii\fontawesome\FAS;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

$this->setPageTitle(Yii::t('app', 'Resend Account Confirmation'));

if (!Yii::$app->getUser()->getIsGuest()) {
    $this->setBreadcrumb(Yii::t('app', 'Account'), ['update']);
}

$this->setBreadcrumb($this->title);
?>
<?= Html::errorSummary($form, [
    'header' => Yii::t('app', 'Your confirmation could not be resend'),
]); ?>
<div class="container">
    <div class="centered">
        <?php Panel::begin(['title' => $this->title]); ?>
        <p><?= Yii::t('app', 'Enter your email address and we will send you another email to confirm your account.'); ?></p>
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
            <?= Html::submitButton(Yii::t('app', 'Send Email'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php ActiveForm::end(); ?>
        <?php Panel::end(); ?>
    </div>
    <?php
    if (Yii::$app->getUser()->getIsGuest()) {
        ?>
        <div class="list-group">
            <a href="<?php echo Url::to(['login']); ?>" class="list-group-item">
                <?= FAS::icon('sign-in-alt', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('app', 'Back to login'); ?>
            </a>
        </div>
        <?php
    }
    ?>
</div>