<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\CredentialController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $credentials
 * @var User $user
 */

use Hirtz\Skeleton\Models\User;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\CredentialGridView;
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
    ->grid(CredentialGridView::make()
        ->provider($credentials)
        ->isOwn(false));
