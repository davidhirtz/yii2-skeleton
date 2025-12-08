<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UserLoginController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\UserLoginGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Logins'))
    ->addBreadcrumb(Yii::t('skeleton', 'Logins'), ['index']);


echo UserSubmenu::make();

echo GridContainer::make()
    ->grid(UserLoginGridView::make()
        ->provider($provider));
