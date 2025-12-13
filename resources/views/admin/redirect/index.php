<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\RedirectController::actionIndex()
 *
 * @var View $this
 * @var RedirectActiveDataProvider $provider
 */


use Hirtz\Skeleton\Modules\Admin\Data\RedirectActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\RedirectGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\RedirectSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo RedirectSubmenu::make()
    ->title(Yii::t('skeleton', 'Redirects'));

echo GridContainer::make()
    ->grid(RedirectGridView::make()
        ->provider($provider));
