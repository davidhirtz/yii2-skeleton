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
use yii\helpers\Html;

$this->setPageTitle(Yii::t('app', 'Logins'));

$this->setBreadcrumb(Yii::t('app', 'Users'), ['/admin/user/index']);
$this->setBreadcrumb($user->getUsername(), ['/admin/user/update', 'id'=>$user->id]);
$this->setBreadcrumb($this->title);
?>
<h1 class="page-header">
	<?= Html::a(Html::encode($user->getUsername()), ['/admin/user/update', 'id'=>$user->id]); ?>
</h1>

<?= UserSubmenu::widget(['user'=>$user]); ?>

<?= Panel::widget([
	'content'=>UserLoginGridView::widget([
		'dataProvider'=>$provider,
		'model'=>$user,
	]),
]); ?>