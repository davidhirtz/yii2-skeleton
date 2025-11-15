<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $form
 */

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserDeletePanel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserHelpPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;

$this->setTitle(Yii::t('skeleton', 'Edit User'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::widget([
    'user' => $form->user,
]);

echo ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'The user could not be updated'));

echo Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $form,
    ]),
]);

echo UserHelpPanel::widget([
    'user' => $form->user,
]);

if (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $form->user])) {
    echo UserDeletePanel::widget([
        'user' => $form->user,
    ]);
}
