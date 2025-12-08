<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\LogController::actionView()
 *
 * @var View $this
 * @var LogDataProvider $provider
 */

use Hirtz\Skeleton\modules\admin\data\LogDataProvider;
use Hirtz\Skeleton\modules\admin\widgets\grids\LogGridView;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;
use Hirtz\Skeleton\widgets\navs\Header;

$this->title(Yii::t('skeleton', 'System'));

echo Header::make()
    ->title(Yii::t('skeleton', 'Logs'));

echo GridContainer::make()
    ->grid(LogGridView::make()
        ->provider($provider));
