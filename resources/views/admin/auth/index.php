<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AuthItemGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;
use yii\data\ActiveDataProvider;

echo Header::make()
    ->title(Yii::t('skeleton', 'Permissions'));

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider));
