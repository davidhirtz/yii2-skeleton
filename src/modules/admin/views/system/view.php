<?php

declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController::actionView()
 *
 * @var View $this
 * @var LogDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\LogFileGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;

$this->setTitle(Yii::t('skeleton', 'System'));

echo Submenu::widget([
    'title' => Yii::t('skeleton', 'Logs'),
]);

echo Panel::widget([
    'content' => LogFileGridView::widget([
        'dataProvider' => $provider,
    ])
]);
