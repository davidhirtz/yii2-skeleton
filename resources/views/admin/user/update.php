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
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo UserHeader::make()
    ->model($form->user);

echo UserSubmenu::make()
    ->model($form->user);

echo FormContainer::make()
    ->title(Yii::t('skeleton', 'Update User'))
    ->form(UserActiveForm::make()
        ->model($form));

if (Yii::$app->getUser()->can(User::AUTH_USER_DELETE, ['user' => $form->user])) {
    echo FormContainer::make()
        ->danger()
        ->title(Yii::t('skeleton', 'Delete User'))
        ->form(UserDeleteActiveForm::make()
            ->model($form->user)
            ->property('email'));
}
