<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\RedirectController::actionIndex()
 *
 * @var View $this
 * @var RedirectActiveDataProvider $provider
 */


use davidhirtz\yii2\skeleton\modules\admin\data\RedirectActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\RedirectGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\RedirectSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Redirects'));

echo GridContainer::make()
    ->grid(RedirectGridView::make()
        ->provider($provider));
