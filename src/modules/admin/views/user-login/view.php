<?php
/**
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserLoginController::actionView()
 *
 * @var View $this
 * @var ActiveDataProvider $provider
 * @var \davidhirtz\yii2\skeleton\models\User $user
 */

use davidhirtz\yii2\skeleton\modules\admin\widgets\grids\UserLoginGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\navs\UserSubmenu;
use davidhirtz\yii2\skeleton\web\View;
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use yii\data\ActiveDataProvider;

$this->setTitle(Yii::t('skeleton', 'Logins'));
$this->setBreadcrumb(Yii::t('skeleton', 'Users'), ['/admin/user/index']);
?>
<?= UserSubmenu::widget(['user' => $user]); ?>

<?= Panel::widget([
    'content' => UserLoginGridView::widget([
        'dataProvider' => $provider,
        'user' => $user,
    ]),
]); ?>