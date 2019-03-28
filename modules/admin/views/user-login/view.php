<?php
/**
 * User login list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionView()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */

use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Logins'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
?>
<?= UserSubmenu::widget(['user' => $user]); ?>

<?= Panel::widget([
    'content' => UserLoginGridView::widget([
        'dataProvider' => $provider,
        'model' => $user,
    ]),
]); ?>