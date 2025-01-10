<?php
declare(strict_types=1);

/**
 * Admin user form
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $form
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

<div class="alert alert-success">
    <div class="alert-heading"><?= Yii::t('skeleton', 'The user will be logged out after saving the form.') ?></div>
    <ul>
        <li>First error message because of email</li>
        <li>Second error <a href="/">message because of password</a></li>
    </ul>
</div>

<?= UserSubmenu::widget([
    'user' => $form->user,
]); ?>

<?= Html::errorSummary($form, [
    'header' => Yii::t('skeleton', 'The user could not be updated'),
]); ?>

<?= Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $form,
    ]),
]);
?>

<?= UserHelpPanel::widget([
    'user' => $form->user,
]); ?>

<?php if (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $form->user])) {
    echo UserDeletePanel::widget([
        'user' => $form->user,
    ]);
} ?>
