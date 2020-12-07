<?php
/**
 * Trail list
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\TrailController::actionIndex()
 *
 * @var View $this
 * @var TrailActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\TrailGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('skeleton', 'Trail'));
?>

<?= Panel::widget([
    'content' => TrailGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>