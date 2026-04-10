<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\LogController::actionIndex()
 *
 * @var View $this
 * @var LogDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\LogFileGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\LogHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo LogHeader::make();

echo GridContainer::make()
    ->grid(LogFileGridView::make()
        ->provider($provider));
