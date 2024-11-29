<?php
declare(strict_types=1);

/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\AuthItemGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Permissions'));
$this->setBreadcrumb(Yii::t('skeleton', 'Permissions'), ['index']);
?>

<?= UserSubmenu::widget(); ?>

<?= Panel::widget([
    'content' => AuthItemGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>
