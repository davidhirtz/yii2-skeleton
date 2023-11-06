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
use davidhirtz\yii2\skeleton\models\forms\LoginForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\skeleton\helpers\Html;
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
        <?php Panel::begin(['title' => $this->title]); ?>
        <?php
        $af = ActiveForm::begin([
            'enableClientValidation' => false,
        ]);

        echo $af->field($form, 'email', ['icon' => 'envelope', 'enableError' => false])->textInput([
            'autocomplete' => 'username',
            'autofocus' => !$form->hasErrors(),
            'type' => 'email',
        ]);

        echo $af->field($form, 'password', ['icon' => 'key', 'enableError' => false])->passwordInput([
            'autocomplete' => 'current-password',
        ]);

        if (Yii::$app->getUser()->enableAutoLogin) {
            echo $af->field($form, 'rememberMe')->checkbox();
        }
        ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('skeleton', 'Login'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php $af::end(); ?>
        <?php Panel::end(); ?>
        <div class="list-group">
            <?php
            if ($form->isFacebookLoginEnabled()) {
                ?>
                <a href="<?= Url::to(['auth', 'authclient' => 'facebook']); ?>" class="list-group-item list-group-item-action">
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