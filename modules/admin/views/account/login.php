<?php
/**
 * Login form.
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\AccountController::actionLogin()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 * @var yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use rmrevin\yii\fontawesome\FAB;
use rmrevin\yii\fontawesome\FAS;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Login'));
$this->setBreadcrumb($this->title);
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
        <div class="list-group">
            <?php
            if ($form->isFacebookLoginEnabled()) {
                ?>
                <a href="<?= Url::to(['auth', 'client' => 'facebook']); ?>" class="list-group-item">
                    <?= FAB::icon('facebook-f', ['class' => 'fa-fw']); ?>
                    <?= Yii::t('skeleton', 'Login with Facebook'); ?>
                </a>
                <?php
            }
            ?>
        </div>
        <?php Panel::begin(['title' => $this->title]); ?>
        <?php
        $af = ActiveForm::begin([
            'enableClientValidation' => false,
        ]);

        echo $af->field($form, 'email', ['icon' => 'envelope', 'enableError' => false])->textInput([
            'autofocus' => !$form->hasErrors(),
            'type' => 'email',
        ]);

        echo $af->field($form, 'password', ['icon' => 'key', 'enableError' => false])->passwordInput();

        if (Yii::$app->getUser()->enableAutoLogin) {
            echo $af->field($form, 'rememberMe')->checkbox();
        }
        ?>
        <div class="form-group">
            <?= Html::submitButton(Yii::t('skeleton', 'Login'), ['class' => 'btn btn-primary btn-block']) ?>
        </div>
        <?php $af->end(); ?>
        <?php Panel::end(); ?>
        <div class="list-group">
            <?php
            if (Yii::$app->getUser()->isSignupEnabled()) {
                ?>
                <a href="<?php echo Url::to(['create']); ?>" class="list-group-item list-group-item-action">
                    <?= FAS::icon('user', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'Create new account'); ?>
                </a>
                <?php
            }

            if (Yii::$app->getUser()->isPasswordResetEnabled()) {
                if (!Yii::$app->getUser()->isUnconfirmedEmailLoginEnabled()) {
                    ?>
                    <a href="<?php echo Url::to(['resend']); ?>" class="list-group-item list-group-item-action">
                        <?= FAS::icon('envelope', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'Resend email confirmation'); ?>
                    </a>
                    <?php
                }
                ?>
                <a href="<?php echo Url::to(['recover']); ?>" class="list-group-item list-group-item-action">
                    <?= FAS::icon('key', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'I forgot my password'); ?>
                </a>
                <?php
            }
            ?>
        </div>
    </div>
</div>