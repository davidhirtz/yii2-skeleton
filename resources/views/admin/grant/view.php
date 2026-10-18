<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\GrantController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $grants
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\GrantGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

echo UserHeader::make()
    ->model($user);

echo UserSubmenu::make()
    ->model($user);

echo GridContainer::make()
    ->grid(GrantGridView::make()
        ->provider($grants));
