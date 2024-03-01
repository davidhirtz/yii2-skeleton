<?php
/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionResend()
 *
 * @var View $this
 * @var AccountResendConfirmForm $form
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\AccountResendConfirmForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\AccountResendConfirmActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Resend Account Confirmation'));
?>
<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Your confirmation could not be resend'),
]); ?>
<div class="container">
    <div class="centered">
        <?= Panel::widget([
            'title' => $this->title,
            'content' => AccountResendConfirmActiveForm::widget([
                'model' => $form,
            ]),
        ]); ?>

        <?php if (Yii::$app->getUser()->getIsGuest()) {
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