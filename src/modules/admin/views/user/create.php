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
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;

$this->setTitle(Yii::t('skeleton', 'Create New User'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::widget([
    'user' => $form->user,
]);

echo ErrorSummary::make()->models($form)
    ->title(Yii::t('skeleton', 'The user could not be created'));

echo Panel::widget([
    'title' => $this->title,
    'content' => UserActiveForm::widget([
        'model' => $form,
    ]),
]);
