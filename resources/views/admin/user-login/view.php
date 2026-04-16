<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UserLoginController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\UserLoginGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserHeader;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

echo UserHeader::make()
    ->provider($provider)
    ->model($user);

echo UserSubmenu::make()
    ->model($user);

echo GridContainer::make()
    ->grid(UserLoginGridView::make()
        ->provider($provider)
        ->user($user));
