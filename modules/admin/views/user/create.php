<?php
/**
 * Create user form.
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionCreate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm $user
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;

$this->setPageTitle(Yii::t('app', 'Create New User'));

$this->setBreadcrumb(Yii::t('app', 'Users'), ['index']);
$this->setBreadcrumb($this->title);
?>

<?= Html::errorSummary($user, [
    'header' => Yii::t('app', 'The user could not be created'),
]); ?>

<?= UserSubmenu::widget([
    'title' => Html::a(Yii::t('app', 'Users'), ['index']),
]); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $user,
    ]),
]);
?>