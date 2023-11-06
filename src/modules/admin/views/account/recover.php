<?php
/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 * @var yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\helpers\Html;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Recover Password'));
?>

<?= Html::errorSummary($form, ['header' => Yii::t('skeleton', 'Your password could not be reset')]); ?>

<div class="container">
    <div class="centered">
        <?php Panel::begin(['title' => $this->title]); ?>
        <p><?= Yii::t('skeleton', 'Enter your email address and we will send you instructions how to reset your password.'); ?></p>
        <?php
        $af = ActiveForm::begin([
            'enableClientValidation' => false,
        ]);
        ?>
        <?= $af->field($form, 'email', ['icon' => 'envelope', 'enableError' => false])->textInput([
            'autocomplete' => 'username',
            'autofocus' => !$form->hasErrors(),
            'type' => 'email',
        ]); ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('skeleton', 'Send Email'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php ActiveForm::end(); ?>
        <?php Panel::end(); ?>
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
</div>
