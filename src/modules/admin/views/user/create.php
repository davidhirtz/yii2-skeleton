<?php

declare(strict_types=1);

/**
 * @see UserController::actionCreate()
 *
 * @var View $this
 * @var UserForm $form
 */

use Hirtz\Skeleton\modules\admin\controllers\UserController;
use Hirtz\Skeleton\modules\admin\models\forms\UserForm;
use Hirtz\Skeleton\modules\admin\widgets\forms\UserActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\FormContainer;

$this->title(Yii::t('skeleton', 'Create New User'))
    ->addBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::make();

echo FormContainer::make()
    ->title($this->title)
    ->form(UserActiveForm::make()
        ->model($form));
