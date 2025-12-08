<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\LogController::actionView()
 *
 * @var View $this
 * @var LogDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Data\LogDataProvider;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\LogGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'System'));

echo Header::make()
    ->title(Yii::t('skeleton', 'Logs'));

echo GridContainer::make()
    ->grid(LogGridView::make()
        ->provider($provider));
