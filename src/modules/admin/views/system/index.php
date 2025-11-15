<?php

declare(strict_types=1);

/**
 * @see SystemController::actionIndex()
 * @var View $this
 */

use davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AssetGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\CacheGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\LogGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\SessionGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

$this->setTitle(Yii::t('skeleton', 'System'));

$blocks = [
    Yii::t('skeleton', 'Logs') => LogGridView::make(),
//    Yii::t('skeleton', 'Assets') => AssetGridView::make(),
//    Yii::t('skeleton', 'Cache') => CacheGridView::make(),
//    Yii::t('skeleton', 'Sessions') => SessionGridView::make(),
];

foreach ($blocks as $title => $grid) {
    echo Submenu::widget([
        'title' => $title,
    ]);
    echo GridContainer::make()
        ->grid($grid);
}