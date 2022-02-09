<?php
/**
 * User permissions
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\AuthController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var User $user
 */

use davidhirtz\yii2\skeleton\models\User;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\AuthItemGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Edit Permissions'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
?>

<?= UserSubmenu::widget(['user' => $user]); ?>

<?= Panel::widget([
    'content' => AuthItemGridView::widget([
        'dataProvider' => $provider,
        'user' => $user,
    ]),
]); ?>