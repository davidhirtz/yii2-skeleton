<?php
/**
 * Create a user.
 * @see UserController::actionCreate()
 *
 * @var View $this
 * @var UserForm $user
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('skeleton', 'Create New User'));
?>

<?= UserSubmenu::widget([
    'user' => $user,
]); ?>

<?= Html::errorSummary($user, [
    'header' => Yii::t('skeleton', 'The user could not be created'),
]); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $user,
    ]),
]);
?>