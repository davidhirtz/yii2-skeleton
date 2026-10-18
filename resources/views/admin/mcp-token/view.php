<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\McpTokenController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $tokens
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\McpTokenGridView;
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
    ->grid(McpTokenGridView::make()
        ->provider($tokens)
        ->isOwn(false));
