<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\html\Container;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserOwnerPanel;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Users'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['index']);

echo UserSubmenu::widget();

echo GridContainer::make()
    ->grid(UserGridView::make()
            ->provider($provider));

if (Yii::$app->getUser()->getIdentity()->isOwner()) {
    echo Container::make()
        ->content(UserOwnerPanel::widget());
}
