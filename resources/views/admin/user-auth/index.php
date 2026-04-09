<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UserAuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AuthItemGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

echo UserSubmenu::make()
    ->user($user);

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider)
        ->user($user));
