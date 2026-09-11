<?php

declare(strict_types=1);

/**
 * @see UserController::actionCreate()
 *
 * @var View $this
 * @var UserForm $form
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\UserController;
use Hirtz\Skeleton\Modules\Admin\Models\forms\UserForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\UserActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo UserHeader::make()
    ->title(Yii::t('skeleton', 'USER_CREATE_TITLE'));

echo FormContainer::make()
    ->form(UserActiveForm::make()
        ->model($form));
