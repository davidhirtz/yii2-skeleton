<?php
/**
 * Auth item list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\AuthItemGridView;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Permissions'));
?>

<?= UserSubmenu::widget(); ?>

<?= Panel::widget([
    'content' => AuthItemGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>