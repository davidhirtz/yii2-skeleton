<?php
/**
 * Trail list
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\TrailController::actionIndex()
 *
 * @var View $this
 * @var TrailActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\data\TrailActiveDataProvider;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\TrailGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\TrailSubmenu;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;

$this->setTitle(Yii::t('skeleton', 'History'));
$this->setBreadcrumb(Yii::t('skeleton', 'History'), ['index']);

if ($provider->user) {
    echo UserSubmenu::widget([
        'user' => $provider->user,
    ]);
} else {
    echo TrailSubmenu::widget([
        'dataProvider' => $provider,
    ]);
}
echo Panel::widget([
    'content' => TrailGridView::widget([
        'dataProvider' => $provider,
    ]),
]);
