<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\TrailController::actionIndex()
 *
 * @var View $this
 * @var TrailActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Data\TrailActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\TrailGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\TrailHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

$this->title(Yii::t('skeleton', 'History'));

echo $provider->user
    ? UserSubmenu::make()->user($provider->user)
    : TrailHeader::make()->provider($provider);

echo GridContainer::make()
    ->grid(TrailGridView::make()
        ->provider($provider));
