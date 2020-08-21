<?php
/**
 * Update user form.
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var \davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm $user
 */

use davidhirtz\yii2\skeleton\helpers\Html;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserToolbar;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\DeleteActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;

$this->setTitle(Yii::t('skeleton', 'Edit User'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);
?>

<?= UserSubmenu::widget([
    'user' => $user,
]); ?>

<?= UserToolbar::widget([
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

<?php
if (Yii::$app->getUser()->can('userDelete')) {
    if (!$user->isOwner()) {
        echo Panel::widget([
            'type' => 'danger',
            'title' => Yii::t('skeleton', 'Delete User'),
            'content' => DeleteActiveForm::widget([
                'model' => $user,
                'attribute' => 'email',
                'message' => Yii::t('skeleton', 'Please type the user email in the text field below to delete this user. All related records and files will also be deleted. This cannot be undone, please be certain!')
            ]),
        ]);
    } else {
        ?>
        <div class="alert alert-warning">
            <?= Yii::t('skeleton', 'You cannot delete this user, because it is the owner of this website.'); ?>
        </div>
        <?php
    }
}
?>

