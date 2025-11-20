<?php

declare(strict_types=1);

/**
 * @see UserController::actionCreate()
 *
 * @var View $this
 * @var UserForm $form
 */

use davidhirtz\yii2\skeleton\modules\admin\controllers\UserController;
use davidhirtz\yii2\skeleton\modules\admin\models\forms\UserForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\UserActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;

$this->title(Yii::t('skeleton', 'Create New User'))
    ->addBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::make();

echo FormContainer::make()
    ->title($this->title)
    ->form(UserActiveForm::make()
        ->model($form));
