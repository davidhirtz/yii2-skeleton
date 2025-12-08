<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 * @var View $this
 */

use Hirtz\Skeleton\modules\admin\controllers\SystemController;
use Hirtz\Skeleton\modules\admin\widgets\grids\AssetGridView;
use Hirtz\Skeleton\modules\admin\widgets\grids\CacheGridView;
use Hirtz\Skeleton\modules\admin\widgets\grids\LogFileGridView;
use Hirtz\Skeleton\modules\admin\widgets\grids\SessionGridView;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;
use Hirtz\Skeleton\widgets\navs\Header;

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
