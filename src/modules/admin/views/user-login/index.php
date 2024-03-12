<?php
/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionIndex()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Logins'));
$this->setBreadcrumb(Yii::t('skeleton', 'Logins'), ['index']);
?>

<?= UserSubmenu::widget(); ?>

<?= Panel::widget([
    'content' => UserLoginGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>