<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\TrailController::actionIndex()
 *
 * @var View $this
 * @var TrailActiveDataProvider $provider
 */

use Hirtz\Skeleton\modules\admin\data\TrailActiveDataProvider;
use Hirtz\Skeleton\modules\admin\widgets\grids\TrailGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\TrailSubmenu;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;

$this->title(Yii::t('skeleton', 'History'))
    ->addBreadcrumb(Yii::t('skeleton', 'History'), ['index']);

echo $provider->user
    ? UserSubmenu::make()
        ->user($provider->user)
    : TrailSubmenu::make()
        ->provider($provider);

echo GridContainer::make()
    ->grid(TrailGridView::make()
        ->provider($provider));
