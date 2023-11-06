<?php
/**
 * Admin user form
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $user
 */

use davidhirtz\yii2\skeleton\modules\admin\helpers\Html;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserHelpPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;

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

<?php
if ($user->isOwner()) {
    ?>
    <div class="alert alert-warning">
        <?= Yii::t('skeleton', 'You cannot delete this user, because it is the owner of this website.'); ?>
    </div>
    <?php
} elseif (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $user])) {
    echo Panel::widget([
        'type' => 'danger',
        'title' => Yii::t('skeleton', 'Delete User'),
        'content' => DeleteActiveForm::widget([
            'model' => $user,
            'attribute' => 'email',
            'message' => Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!')
        ]),
    ]);
}
?>

