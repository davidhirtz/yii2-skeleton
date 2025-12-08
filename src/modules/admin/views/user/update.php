<?php

declare(strict_types=1);

/**
 * @see Hirtz\Skeleton\modules\admin\controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $form
 */

use Hirtz\Skeleton\models\User;
use Hirtz\Skeleton\modules\admin\models\forms\UserForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\UserActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\UserDeleteActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\modules\admin\widgets\panels\UserPanel;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\FormContainer;

echo UserSubmenu::make()
    ->user($form->user);

echo FormContainer::make()
    ->title($this->title)
    ->form(UserActiveForm::make()
        ->model($form));

echo UserPanel::make()
    ->user($form->user);

if (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $form->user])) {
    echo FormContainer::make()
        ->danger()
        ->title(Yii::t('skeleton', 'Delete User'))
        ->form(UserDeleteActiveForm::make()
            ->model($form->user)
            ->property('email'));
}
