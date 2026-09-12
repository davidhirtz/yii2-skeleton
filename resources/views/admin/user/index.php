<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UserController::actionIndex()
 *
 * @var View $this
 * @var UserActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Data\UserActiveDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\UserGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserHeader;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Container;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;

echo UserHeader::make()
    ->provider($provider);

echo GridContainer::make()
    ->grid(UserGridView::make()
        ->provider($provider));
