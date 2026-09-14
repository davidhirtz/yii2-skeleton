<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 * @var View $this
 */

use Hirtz\Skeleton\Modules\Admin\Controllers\SystemController;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\AssetBundleGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\CacheGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\SessionGridView;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use Hirtz\Skeleton\Widgets\Navs\Header;

$this->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

echo Header::make()
    ->title(Yii::t('skeleton', 'COMMON_SYSTEM'));

$blocks = [
    Yii::t('skeleton', 'SYSTEM_ASSETS') => AssetBundleGridView::make(),
    Yii::t('skeleton', 'SYSTEM_CACHE') => CacheGridView::make(),
    Yii::t('skeleton', 'SESSION_SESSIONS') => SessionGridView::make(),
];

foreach ($blocks as $title => $grid) {
    echo GridContainer::make()
        ->title($title)
        ->grid($grid);
}
