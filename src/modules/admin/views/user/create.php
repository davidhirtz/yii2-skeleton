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
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

$this->title(Yii::t('skeleton', 'Create New User'))
    ->addBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::make();

echo FormContainer::make()
    ->title($this->title)
    ->form(UserActiveForm::make()
        ->model($form));
