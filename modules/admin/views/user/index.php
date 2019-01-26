<?php
/**
 * Admin user list.
 * @see \davidhirtz\yii2\skeleton\modules\admin\controllers\UserController::actionIndex()
 *
 * @var \davidhirtz\yii2\skeleton\web\View $this
 * @var ActiveDataProvider $provider
 */
use davidhirtz\yii2\skeleton\widgets\bootstrap\Panel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\grid\UserGridView;
use davidhirtz\yii2\skeleton\modules\admin\widgets\panels\UserOwnerPanel;
use davidhirtz\yii2\skeleton\modules\admin\widgets\nav\UserSubmenu;
use yii\data\ActiveDataProvider;
use davidhirtz\yii2\skeleton\helpers\Html;

$this->setPageTitle(Yii::t('app', 'Users'));
$this->setBreadcrumb($this->title, ['index']);
?>

<?= UserSubmenu::widget([
	'title'=>Html::a(Html::encode($this->title), ['index']),
]); ?>

<?= Panel::widget([
	'content'=>UserGridView::widget([
		'dataProvider'=>$provider,
	]),
]); ?>

<?php
if(Yii::$app->getUser()->getIdentity()->getIsOwner())
{
	echo UserOwnerPanel::widget();
}
?>