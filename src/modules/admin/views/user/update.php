<?php

declare(strict_types=1);

/**
 * @see davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $form
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserDeleteActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;

$this->setTitle(Yii::t('skeleton', 'Edit User'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::make()
    ->user($form->user);

echo Container::make()
    ->content(ErrorSummary::make()->models($form)
        ->title(Yii::t('skeleton', 'The user could not be updated')));

echo FormContainer::make()
    ->title($this->title)
    ->form(UserActiveForm::widget([
        'model' => $form,
    ]));

echo UserPanel::make()
    ->user($form->user);

if (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $form->user])) {
    echo FormContainer::make()
        ->danger()
        ->title(Yii::t('skeleton', 'Delete User'))
        ->form(UserDeleteActiveForm::widget([
            'model' => $form->user,
        ]));
}
