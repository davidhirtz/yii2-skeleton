<?php

declare(strict_types=1);

/**
 * @see Hirtz\Skeleton\Modules\Admin\Controllers\UserController::actionUpdate()
 *
 * @var View $this
 * @var UserForm $form
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Models\forms\UserForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\UserActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\UserDeleteActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\UserPanel;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

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
