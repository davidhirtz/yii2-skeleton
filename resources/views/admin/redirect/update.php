<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionUpdate()
 *
 * @var View $this
 * @var Redirect $redirect
 * @var RedirectActiveDataProvider $provider
 */

use Hirtz\Skeleton\Models\Redirect;
use Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController;
use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Forms\RedirectActiveForm;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\RedirectGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\RedirectSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Forms\DeleteActiveForm;
use Hirtz\Skeleton\Widgets\Forms\FormContainer;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Update Redirect'));

echo FormContainer::make()
    ->form(RedirectActiveForm::make()
        ->model($redirect));

echo GridContainer::make()
    ->title(Yii::t('skeleton', 'Additional Redirects'))
    ->grid(RedirectGridView::make()
        ->redirect($redirect));

echo FormContainer::make()
    ->danger()
    ->title(Yii::t('skeleton', 'Delete Redirect'))
    ->form(DeleteActiveForm::make()
        ->model($redirect));
