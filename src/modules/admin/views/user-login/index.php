<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\UserLoginController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\modules\admin\widgets\grids\UserLoginGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Logins'))
    ->addBreadcrumb(Yii::t('skeleton', 'Logins'), ['index']);


echo UserSubmenu::make();

echo GridContainer::make()
    ->grid(UserLoginGridView::make()
        ->provider($provider));
