<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AuthItemGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Permissions'))
    ->addBreadcrumb(Yii::t('skeleton', 'Permissions'), ['index']);

echo Container::make()
    ->content(UserSubmenu::make());

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider));
