<?php
/**
 * Signup form.
 * @see davidhirtz\yii2\skeleton\controllers\UserController::actionCreate()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\SignupForm $user
 * @var yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\ActiveForm;
use rmrevin\yii\fontawesome\FAS;
use davidhirtz\yii2\skeleton\helpers\Html;
use yii\helpers\Url;

\davidhirtz\yii2\skeleton\assets\SignupAsset::register($this);

$this->setPageTitle(Yii::t('app', 'Sign up'));
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($user, [
    'header' => Yii::t('app', 'Your account could not be created'),
]); ?>

<noscript>
    <div class="alert alert-danger">
        <p><?php echo Yii::t('app', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
    </div>
</noscript>

<div class="container">
    <div class="row justify-content-center">
        <div class="centered">
            <?php
            if ($user->isFacebookSignupEnabled()) {
                ?>
                <a href="<?= Url::to(['auth', 'client' => 'facebook']); ?>" class="list-group-item">
                    <?= FAS::icon('facebook', ['class' => 'fa-fw']); ?>
                    <?= Yii::t('app', 'Sign up with Facebook'); ?>
                </a>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="row justify-content-center">
        <div class="centered">
            <?php Panel::begin(['title' => $this->title]); ?>
            <?php
            $af = ActiveForm::begin([
                'model' => $user,
            ]);

            $this->registerJs("jQuery('#{$af->id}').signupForm();");

            echo $af->field($user, 'name', ['inputOptions' => ['autofocus' => !$user->hasErrors()], 'icon' => 'user']);
            echo $af->field($user, 'email', ['inputOptions' => ['type' => 'email'], 'icon' => 'envelope']);
            echo $af->field($user, 'password', ['icon' => 'key'])->passwordInput();
            echo $af->field($user, 'terms', ['enableError' => false])->checkbox();
            ?>
            <div class="form-group">
                <?= Html::activeHiddenInput($user, 'honeypot', ['id' => 'honeypot']); ?>
                <?= Html::activeHiddenInput($user, 'token', ['id' => 'token', 'data-url' => Url::to(['token'])]); ?>
                <?= Html::activeHiddenInput($user, 'timezone', ['id' => 'tz']); ?>
                <button type="submit" class="btn btn-primary btn-block"><?= Yii::t('app', 'Create Account'); ?></button>
            </div>
            <?php ActiveForm::end(); ?>
            <?php Panel::end(); ?>
            <div class="list-group">
                <a href="<?php echo Url::to(['login']); ?>" class="list-group-item">
                    <?= FAS::icon('sign-in-alt', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('app', 'Back to login'); ?>
                </a>
            </div>
        </div>
    </div>
</div>