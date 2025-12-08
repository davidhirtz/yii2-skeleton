<?php

declare(strict_types=1);

/**
 * @see \Hirtz\Skeleton\modules\admin\controllers\UserController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use Hirtz\Skeleton\html\Container;
use Hirtz\Skeleton\modules\admin\widgets\grids\UserGridView;
use Hirtz\Skeleton\modules\admin\widgets\navs\UserSubmenu;
use Hirtz\Skeleton\modules\admin\widgets\panels\UserOwnerPanel;
use Hirtz\Skeleton\web\View;
use Hirtz\Skeleton\widgets\grids\GridContainer;
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
