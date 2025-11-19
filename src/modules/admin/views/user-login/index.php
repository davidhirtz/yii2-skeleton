<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;
use yii\data\ActiveDataProvider;

$this->title(Yii::t('skeleton', 'Logins'));
$this->addBreadcrumb(Yii::t('skeleton', 'Logins'), ['index']);


echo UserSubmenu::make();

echo GridContainer::make()
    ->grid(UserLoginGridView::make()
        ->provider($provider));
