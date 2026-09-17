<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\AuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AuthItemGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\HintAlert;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;
use yii\data\ActiveDataProvider;

echo Header::make()
    ->title(Yii::t('skeleton', 'COMMON_PERMISSIONS'));

echo HintAlert::make()
    ->text(Yii::t('skeleton', 'AUTH_INDEX_HINT'));

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider));
