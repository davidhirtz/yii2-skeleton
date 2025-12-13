<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AssetGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\CacheGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\LogFileGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\SessionGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'System'));

echo Header::make()
    ->title(Yii::t('skeleton', 'System'));

$blocks = [
    Yii::t('skeleton', 'Logs') => LogFileGridView::make(),
    Yii::t('skeleton', 'Assets') => AssetGridView::make(),
    Yii::t('skeleton', 'Cache') => CacheGridView::make(),
    Yii::t('skeleton', 'Sessions') => SessionGridView::make(),
];

foreach ($blocks as $title => $grid) {
    echo GridContainer::make()
        ->title($title)
        ->grid($grid);
}
