<?php
/**
 * Admin user form
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $user
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserDeletePanel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserHelpPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('skeleton', 'Edit User'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);
?>

<?= UserSubmenu::widget([
    'user' => $user,
]); ?>

<?= Html::errorSummary($user, [
    'title' => Yii::t('skeleton', 'The user could not be updated'),
]); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $user,
    ]),
]);
?>

<?= UserHelpPanel::widget([
    'user' => $user,
]); ?>

<?php if (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $user])) {
    echo UserDeletePanel::widget([
        'user' => $user,
    ]);
} ?>
