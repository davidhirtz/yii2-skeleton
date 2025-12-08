<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\models\Redirect;
use Hirtz\Skeleton\modules\admin\controllers\RedirectController;
use Hirtz\Skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\navs\RedirectSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\ErrorSummary;
use Hirtz\Skeleton\widgets\forms\FormContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Create New Redirect'));

echo Container::make()
    ->content(ErrorSummary::make()
        ->models($redirect));

echo FormContainer::make()
    ->title($this->title)
    ->form(RedirectActiveForm::make()
        ->model($redirect));
