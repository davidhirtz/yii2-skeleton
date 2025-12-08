<?php

declare(strict_types=1);

/**
 * @see RedirectController::actionUpdate()
 *
 * @var View $this
 * @var Redirect $redirect
 * @var RedirectActiveDataProvider $provider
 */

use Hirtz\Skeleton\models\Redirect;
use Hirtz\Skeleton\modules\admin\controllers\RedirectController;
use Hirtz\Skeleton\modules\admin\data\RedirectActiveDataProvider;
use Hirtz\Skeleton\modules\admin\widgets\forms\RedirectActiveForm;
use Hirtz\Skeleton\modules\admin\widgets\grids\RedirectGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\RedirectSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\forms\DeleteActiveForm;
use Hirtz\Skeleton\widgets\forms\FormContainer;
use Hirtz\Skeleton\widgets\grids\GridContainer;

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
