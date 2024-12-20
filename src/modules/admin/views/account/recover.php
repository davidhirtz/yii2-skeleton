<?php
declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\controllers\AccountController::actionRecover()
 *
 * @var View $this
 * @var davidhirtz\yii2\skeleton\models\forms\LoginForm $form
 * @var yii\bootstrap5\ActiveForm $af
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\PasswordRecoverActiveForm;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\helpers\Url;

$this->setTitle(Yii::t('skeleton', 'Recover Password'));
?>

<?= Html::errorSummary($form, ['header' => Yii::t('skeleton', 'Your password could not be reset')]); ?>

<div class="container">
    <div class="centered">
        <?= Panel::widget([
            'title' => $this->title,
            'content' => PasswordRecoverActiveForm::widget([
                'model' => $form,
            ]),
        ]); ?>

        <?php if (Yii::$app->getUser()->getIsGuest()) {
            ?>
            <div class="list-group">
                <a href="<?php echo Url::to(['login']); ?>" class="list-group-item list-group-item-action">
                    <?= Html::iconText('sign-in-alt', Yii::t('skeleton', 'Back to login')); ?>
                </a>
            </div>
            <?php
        } ?>
    </div>
</div>
