<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionCreate()
 *
 * @var View $this
 * @var Redirect $redirect
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\RedirectActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\RedirectSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\ErrorSummary;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Create New Redirect'));

echo Container::make()
    ->content(ErrorSummary::make()
        ->models($redirect));

echo FormContainer::make()
    ->title($this->title)
    ->form(RedirectActiveForm::make()
        ->model($redirect));
