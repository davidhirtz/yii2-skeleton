<?php
/**
 * @see AccountController::actionLogin()
 * @see AccountController::actionResend()
 * @see AccountController::actionRecover()
 *
 * @var View $this
 * @var LoginForm $form
 * @var ActiveForm $af
 */

use davidhirtz\yii2\skeleton\controllers\AccountController;
use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\LoginActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Login'));
?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'Login unsuccessful'),
]); ?>

<noscript>
    <div class="alert alert-danger">
        <div class="alert-heading"><?= Yii::t('skeleton', 'JavaScript is disabled on your browser.'); ?></div>
        <p><?= Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
    </div>
</noscript>

<div class="container">
    <div class="centered">
        <?= Panel::widget([
            'title' => $this->title,
            'content' => LoginActiveForm::widget([
                'model' => $form,
            ])
        ]) ?>
        <div class="list-group">
            <?php
            if ($form->isFacebookLoginEnabled()) {
                ?>
                <a href="<?= Url::to(['auth', 'authclient' => 'facebook']); ?>"
                   class="list-group-item list-group-item-action">
                    <?= Icon::brand('facebook-f', ['class' => 'fa-fw']); ?>
                    <?= Yii::t('skeleton', 'Login with Facebook'); ?>
                </a>
                <?php
            }

            if (Yii::$app->getUser()->isSignupEnabled()) {
                ?>
                <a href="<?= Url::to(['create']); ?>" class="list-group-item list-group-item-action">
                    <?= Icon::tag('user', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'Create new account'); ?>
                </a>
                <?php
            }

            if (Yii::$app->getUser()->isPasswordResetEnabled()) {
                if (!Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
                    ?>
                    <a href="<?= Url::to(['resend']); ?>" class="list-group-item list-group-item-action">
                        <?= Icon::tag('envelope', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'Resend email confirmation'); ?>
                    </a>
                    <?php
                }
                ?>
                <a href="<?= Url::to(['recover']); ?>" class="list-group-item list-group-item-action">
                    <?= Icon::tag('key', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'I forgot my password'); ?>
                </a>
                <?php
            }
?>
        </div>
    </div>
</div>