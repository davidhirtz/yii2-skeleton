<?php
/**
 * Admin user list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserOwnerPanel;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Users'));
?>

<?= UserSubmenu::widget(); ?>

<?= Panel::widget([
    'content' => UserGridView::widget([
        'dataProvider' => $provider,
    ]),
]); ?>

<?php
if (Yii::$app->getUser()->getIdentity()->isOwner()) {
    echo UserOwnerPanel::widget();
}
?>