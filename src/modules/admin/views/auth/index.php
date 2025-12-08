<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\AuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\modules\admin\widgets\grids\AuthItemGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Permissions'))
    ->addBreadcrumb(Yii::t('skeleton', 'Permissions'), ['index']);

echo Container::make()
    ->content(UserSubmenu::make());

echo GridContainer::make()
    ->grid(AuthItemGridView::make()
        ->provider($provider));
