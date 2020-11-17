<?php
/**
 * Error log.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\SystemController::actionIndex()
 *
 * @var View $this
 * @var LogDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\data\LogDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\LogGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\fontawesome\Submenu;

$this->setTitle(Yii::t('skeleton', 'System'));

echo Submenu::widget([
    'title' => Yii::t('skeleton', 'Logs'),
]);

echo LogGridView::widget([
    'dataProvider' => $provider,
]);
?>