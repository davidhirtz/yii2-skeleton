<?php
/**
 * Login list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 * @var string $ipAddress
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Logins'));
?>

<?= UserSubmenu::widget(); ?>

<?= Panel::widget([
    'content' => UserLoginGridView::widget([
        'dataProvider' => $provider,
        'search' => $ipAddress,
    ]),
]); ?>