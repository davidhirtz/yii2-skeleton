<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UserLoginController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\UserLoginGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;
use yii\data\ActiveDataProvider;

echo Header::make()
    ->pagination($provider)
    ->title(Yii::t('skeleton', 'Logins'));

echo GridContainer::make()
    ->grid(UserLoginGridView::make()
        ->provider($provider));
