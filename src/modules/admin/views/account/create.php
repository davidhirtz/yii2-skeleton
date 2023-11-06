<?php
/**
 * @see davidhirtz\yii2\skeleton\controllers\UserController::actionCreate()
 *
 * @var davidhirtz\yii2\skeleton\web\View $this
 * @var davidhirtz\yii2\skeleton\models\forms\SignupForm $user
 * @var yii\bootstrap4\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\SignupActiveForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Icon;
use davidhirtz\yii2\skeleton\modules\admin\helpers\Html;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Sign up'));
?>

<?= Html::errorSummary($user, [
    'header' => Yii::t('skeleton', 'Your account could not be created'),
]); ?>

<noscript>
    <div class="alert alert-danger">
        <p><?php echo Yii::t('skeleton', 'Please enable JavaScript on your browser or upgrade to a JavaScript-capable browser to sign up.'); ?></p>
    </div>
</noscript>

<div class="container">
    <div class="centered">
        <?= Panel::widget([
            'title' => $this->title,
            'content' => SignupActiveForm::widget([
                'model' => $user,
            ]),
        ]); ?>
        <div class="list-group">
            <?php
            if ($user->isFacebookSignupEnabled()) {
                ?>
                <a href="<?= Url::to(['auth', 'authclient' => 'facebook']); ?>" class="list-group-item list-group-item-action">
                    <?= Icon::brand('facebook-f', ['class' => 'fa-fw']); ?>
                    <?= Yii::t('skeleton', 'Sign up with Facebook'); ?>
                </a>
                <?php
            }
            ?>
            <a href="<?php echo Url::to(['login']); ?>" class="list-group-item list-group-item-action">
                <?= Icon::tag('sign-in-alt', ['class' => 'fa-fw icon-left']); ?><?= Yii::t('skeleton', 'Back to login'); ?>
            </a>
        </div>
    </div>
</div>