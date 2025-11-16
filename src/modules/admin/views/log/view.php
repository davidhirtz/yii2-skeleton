<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\LogController::actionView()
 *
 * @var View $this
 * @var LogDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\LogGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;
use davidhirtz\yii2\skeleton\widgets\grids\GridContainer;

$this->setTitle(Yii::t('skeleton', 'System'));

echo Submenu::widget([
    'title' => Yii::t('skeleton', 'Logs'),
]);

echo GridContainer::make()
    ->grid(LogGridView::make()
        ->provider($provider));
