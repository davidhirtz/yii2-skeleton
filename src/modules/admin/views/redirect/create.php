<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\models\Redirect;
use davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\RedirectSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\forms\ErrorSummary;
use davidhirtz\yii2\skeleton\widgets\forms\FormContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Create New Redirect'));

echo Container::make()
    ->content(ErrorSummary::make()
        ->models($redirect));

echo FormContainer::make()
    ->title($this->title)
    ->form(RedirectActiveForm::make()
        ->model($redirect));
