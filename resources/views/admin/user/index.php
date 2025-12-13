<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\Modules\Admin\Controllers\UserController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\Html\Container;
use Hirtz\Skeleton\Modules\Admin\Widgets\Grids\UserGridView;
use Hirtz\Skeleton\Modules\Admin\Widgets\Navs\UserSubmenu;
use Hirtz\Skeleton\Modules\Admin\Widgets\Panels\UserOwnerPanel;
use Hirtz\Skeleton\Web\View;
use Hirtz\Skeleton\Widgets\Grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Users'))
    ->addBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::make();

echo GridContainer::make()
    ->grid(UserGridView::make()
            ->provider($provider));

if (Yii::$app->getUser()->getIdentity()->isOwner()) {
    echo Container::make()
        ->content(UserOwnerPanel::make());
}
