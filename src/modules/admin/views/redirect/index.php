<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\RedirectController::actionIndex()
 *
 * @var View $this
 * @var RedirectActiveDataProvider $provider
 */


use Hirtz\Skeleton\modules\admin\data\RedirectActiveDataProvider;
use Hirtz\Skeleton\modules\admin\widgets\grids\RedirectGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\RedirectSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Redirects'));

echo GridContainer::make()
    ->grid(RedirectGridView::make()
        ->provider($provider));
